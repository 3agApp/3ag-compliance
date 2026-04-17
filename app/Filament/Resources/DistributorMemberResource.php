<?php

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\DistributorMemberResource\Pages;
use App\Models\Distributor;
use App\Models\Supplier;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class DistributorMemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|UnitEnum|null $navigationGroup = 'Distributor';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Members';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Member';

    protected static ?string $slug = 'members';

    protected static bool $isScopedToTenant = false;

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => static::getTableQuery())
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('membership_role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? Role::from($state)->getLabel() : null),
                TextColumn::make('membership_supplier_name')
                    ->label('Supplier')
                    ->placeholder('—'),
                TextColumn::make('membership_joined_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('changeRole')
                    ->label('Change Role')
                    ->icon('heroicon-o-shield-check')
                    ->form([
                        Select::make('role')
                            ->options(fn () => collect(Role::cases())
                                ->mapWithKeys(fn (Role $role) => [$role->value => $role->getLabel()]))
                            ->live()
                            ->default(fn (User $record): ?string => $record->membership_role)
                            ->required(),
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->options(fn (): array => static::getSupplierOptions())
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->visible(fn ($get): bool => $get('role') === Role::Supplier->value)
                            ->required(fn ($get): bool => $get('role') === Role::Supplier->value)
                            ->default(fn (User $record): mixed => $record->membership_supplier_id),
                    ])
                    ->action(function (User $record, array $data): void {
                        $tenant = Filament::getTenant();
                        $supplierId = static::resolveSupplierId($tenant, $data);

                        if ($data['role'] !== Role::Owner->value) {
                            $ownerCount = $tenant->members()
                                ->wherePivot('role', Role::Owner->value)
                                ->count();

                            $currentRole = $record->getRoleForDistributor($tenant);

                            if ($currentRole === Role::Owner && $ownerCount <= 1) {
                                Notification::make()
                                    ->danger()
                                    ->title('Cannot change role')
                                    ->body('Distributor must have at least one owner.')
                                    ->send();

                                return;
                            }
                        }

                        $tenant->members()->updateExistingPivot($record->id, [
                            'role' => $data['role'],
                            'supplier_id' => $supplierId,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Role updated')
                            ->send();
                    })
                    ->visible(fn (): bool => Filament::auth()->user()
                        ->getRoleForDistributor(Filament::getTenant())
                        ?->canManageMembers() ?? false),

                Action::make('removeMember')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $tenant = Filament::getTenant();
                        $role = $record->getRoleForDistributor($tenant);

                        if ($role === Role::Owner) {
                            $ownerCount = $tenant->members()
                                ->wherePivot('role', Role::Owner->value)
                                ->count();

                            if ($ownerCount <= 1) {
                                Notification::make()
                                    ->danger()
                                    ->title('Cannot remove member')
                                    ->body('Cannot remove the last owner of the distributor.')
                                    ->send();

                                return;
                            }
                        }

                        $tenant->members()->detach($record->id);

                        Notification::make()
                            ->success()
                            ->title('Member removed')
                            ->send();
                    })
                    ->hidden(fn (User $record): bool => $record->id === Filament::auth()->id())
                    ->visible(fn (): bool => Filament::auth()->user()
                        ->getRoleForDistributor(Filament::getTenant())
                        ?->canManageMembers() ?? false),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistributorMembers::route('/'),
        ];
    }

    /**
     * @return array<int, array-key>
     */
    private static function getSupplierOptions(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Distributor) {
            return [];
        }

        return Supplier::query()
            ->whereBelongsTo($tenant)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function getTableQuery(): Builder
    {
        $query = User::query()
            ->select('users.*')
            ->selectRaw('distributor_user.role as membership_role')
            ->selectRaw('distributor_user.created_at as membership_joined_at')
            ->join('distributor_user', fn ($join) => $join
                ->on('users.id', '=', 'distributor_user.user_id')
                ->where('distributor_user.distributor_id', Filament::getTenant()->id));

        if (! Schema::hasColumn('distributor_user', 'supplier_id')) {
            return $query
                ->selectRaw('null as membership_supplier_id')
                ->selectRaw('null as membership_supplier_name');
        }

        return $query
            ->selectRaw('distributor_user.supplier_id as membership_supplier_id')
            ->selectRaw('suppliers.name as membership_supplier_name')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'distributor_user.supplier_id');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resolveSupplierId(Distributor $tenant, array $data): ?int
    {
        $role = Role::from($data['role']);

        if ($role !== Role::Supplier) {
            return null;
        }

        $supplierId = $data['supplier_id'] ?? null;

        if (! filled($supplierId)) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Select a supplier for supplier members.',
            ]);
        }

        $exists = Supplier::query()
            ->whereBelongsTo($tenant)
            ->whereKey($supplierId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Select a valid supplier for this distributor.',
            ]);
        }

        return (int) $supplierId;
    }
}
