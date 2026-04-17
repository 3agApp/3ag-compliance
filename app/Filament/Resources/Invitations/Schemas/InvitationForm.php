<?php

namespace App\Filament\Resources\Invitations\Schemas;

use App\Enums\Role;
use App\Models\Distributor;
use App\Models\Supplier;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class InvitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invitation information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('user@example.com'),
                        Select::make('role')
                            ->options(fn () => collect([Role::Admin, Role::Supplier])
                                ->mapWithKeys(fn (Role $role) => [$role->value => $role->getLabel()]))
                            ->default(Role::Admin->value)
                            ->live()
                            ->native(false)
                            ->required(),
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->options(fn (): array => static::getSupplierOptions())
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('role') === Role::Supplier->value)
                            ->required(fn (Get $get): bool => $get('role') === Role::Supplier->value),
                    ]),
            ]);
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
}
