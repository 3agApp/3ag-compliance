<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationAcceptController extends Controller
{
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $invitation = Invitation::with('distributor')
            ->where('token', $token)
            ->firstOrFail();

        if (! $request->hasValidSignature()) {
            Notification::make()
                ->danger()
                ->title('This invitation link is invalid or has expired.')
                ->send();

            return redirect()->route('filament.dashboard.auth.login');
        }

        if ($invitation->isAccepted()) {
            Notification::make()
                ->warning()
                ->title('This invitation has already been accepted.')
                ->send();

            return redirect()->route('filament.dashboard.auth.login');
        }

        if ($invitation->isExpired()) {
            Notification::make()
                ->danger()
                ->title('This invitation has expired. Please request a new one.')
                ->send();

            return redirect()->route('filament.dashboard.auth.login');
        }

        $existingUser = User::where('email', $invitation->email)->first();

        session(['invitation_confirmation_token' => $token]);

        return view('invitations.accept', [
            'invitation' => $invitation,
            'existingUser' => $existingUser,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        if (session('invitation_confirmation_token') !== $token) {
            Notification::make()
                ->danger()
                ->title('Open the invitation link again before confirming.')
                ->send();

            return redirect()->route('filament.dashboard.auth.login');
        }

        $invitation = Invitation::with('distributor')
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isAccepted()) {
            Notification::make()
                ->warning()
                ->title('This invitation has already been accepted.')
                ->send();

            return redirect()->route('filament.dashboard.auth.login');
        }

        if ($invitation->isExpired()) {
            Notification::make()
                ->danger()
                ->title('This invitation has expired. Please request a new one.')
                ->send();

            return redirect()->route('filament.dashboard.auth.login');
        }

        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            if (Auth::check() && Auth::id() !== $existingUser->id) {
                Notification::make()
                    ->warning()
                    ->title('Sign in with the invited account to accept this invitation.')
                    ->send();

                return redirect()->route('filament.dashboard.auth.login');
            }

            if (! $existingUser->distributors()->whereKey($invitation->distributor_id)->exists()) {
                $existingUser->distributors()->attach($invitation->distributor_id, [
                    'role' => $invitation->role->value,
                    'supplier_id' => $invitation->supplier_id,
                ]);
            }

            $invitation->update(['accepted_at' => now()]);
            session()->forget('invitation_confirmation_token');

            Notification::make()
                ->success()
                ->title('Invitation accepted.')
                ->send();

            if (Auth::check()) {
                return redirect()->route('filament.dashboard.pages.dashboard', [
                    'tenant' => $invitation->distributor->slug,
                ]);
            }

            return redirect()->route('filament.dashboard.auth.login');
        }

        session()->forget('invitation_confirmation_token');
        session(['pending_invitation_token' => $token]);

        Notification::make()
            ->info()
            ->title('Please create an account to join the distributor.')
            ->send();

        return redirect()->route('filament.dashboard.auth.register');
    }
}
