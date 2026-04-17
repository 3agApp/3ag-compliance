<?php

namespace App\Filament\Pages\Auth;

use App\Models\Invitation;
use Filament\Auth\Pages\Register as FilamentRegister;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class Register extends FilamentRegister
{
    protected function afterRegister(): void
    {
        $token = session('pending_invitation_token');

        if (! $token) {
            return;
        }

        $invitation = Invitation::with('distributor')
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->first();

        if (! $invitation) {
            session()->forget('pending_invitation_token');

            return;
        }

        if ($invitation->isExpired()) {
            session()->forget('pending_invitation_token');

            Notification::make()
                ->danger()
                ->title('This invitation has expired. Please request a new one.')
                ->send();

            return;
        }

        /** @var Model $user */
        $user = $this->getUser();

        if ($user->email !== $invitation->email) {
            session()->forget('pending_invitation_token');

            Notification::make()
                ->warning()
                ->title('This invitation is for a different email address.')
                ->body('Sign in with the invited email address to join the distributor.')
                ->send();

            return;
        }

        Notification::make()
            ->info()
            ->title('Pending invitation found.')
            ->body('Review your invitation before creating a distributor.')
            ->send();

        if (Route::has('filament.dashboard.tenant.registration')) {
            session()->put('url.intended', route('filament.dashboard.tenant.registration'));
        }
    }

    protected function getUser(): Model
    {
        return $this->form->model;
    }
}
