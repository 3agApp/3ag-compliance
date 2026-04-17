<?php

namespace App\Filament\Pages\Tenancy;

use App\Enums\Role;
use App\Models\Distributor;
use App\Models\Invitation;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RegisterDistributor extends RegisterTenant
{
    protected string $view = 'filament.pages.tenancy.register-distributor';

    public bool $showRegistrationForm = false;

    public static function getLabel(): string
    {
        return 'Register Distributor';
    }

    public function getTitle(): string
    {
        if ($this->hasPendingInvitations() && (! $this->showRegistrationForm)) {
            return 'Pending Invitations';
        }

        return static::getLabel();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Distributor::class, 'slug')
                    ->rules(['alpha_dash:ascii']),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $distributor = Distributor::create($data);

        $distributor->members()->attach(Filament::auth()->id(), [
            'role' => Role::Owner->value,
        ]);

        return $distributor;
    }

    public function hasPendingInvitations(): bool
    {
        return $this->getPendingInvitations()->isNotEmpty();
    }

    /**
     * @return Collection<int, Invitation>
     */
    public function getPendingInvitations(): Collection
    {
        $user = Filament::auth()->user();

        if ($user === null) {
            return Invitation::newCollection();
        }

        return Invitation::query()
            ->with(['distributor', 'supplier'])
            ->where('email', $user->email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->get();
    }

    public function acceptInvitation(int $invitationId): void
    {
        $user = Filament::auth()->user();

        if ($user === null) {
            return;
        }

        $invitation = $this->getPendingInvitations()
            ->firstWhere('id', $invitationId);

        if (! $invitation instanceof Invitation) {
            Notification::make()
                ->danger()
                ->title('Invitation not found.')
                ->send();

            return;
        }

        if (! $user->distributors()->whereKey($invitation->distributor_id)->exists()) {
            $user->distributors()->attach($invitation->distributor_id, [
                'role' => $invitation->role->value,
                'supplier_id' => $invitation->supplier_id,
            ]);
        }

        $invitation->update(['accepted_at' => now()]);
        session()->forget('pending_invitation_token');

        Notification::make()
            ->success()
            ->title('Invitation accepted.')
            ->body('You can now access this distributor.')
            ->send();

        $this->redirect(route('filament.dashboard.pages.dashboard', [
            'tenant' => $invitation->distributor->slug,
        ]), navigate: true);
    }

    public function rejectInvitation(int $invitationId): void
    {
        $invitation = $this->getPendingInvitations()
            ->firstWhere('id', $invitationId);

        if (! $invitation instanceof Invitation) {
            Notification::make()
                ->danger()
                ->title('Invitation not found.')
                ->send();

            return;
        }

        if (session('pending_invitation_token') === $invitation->token) {
            session()->forget('pending_invitation_token');
        }

        $invitation->delete();

        Notification::make()
            ->success()
            ->title('Invitation rejected.')
            ->send();

        if (! $this->hasPendingInvitations()) {
            $this->showRegistrationForm = true;
        }
    }

    public function showDistributorRegistrationForm(): void
    {
        $this->showRegistrationForm = true;
    }
}
