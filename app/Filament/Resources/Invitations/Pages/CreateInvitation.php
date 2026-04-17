<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Enums\Role;
use App\Filament\Resources\Invitations\InvitationResource;
use App\Mail\InvitationMail;
use App\Models\Distributor;
use App\Models\Invitation;
use App\Models\Supplier;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateInvitation extends CreateRecord
{
    protected static string $resource = InvitationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant();
        $supplierId = $this->resolveSupplierId($tenant, $data);

        $existingMember = $tenant->members()
            ->where('email', $data['email'])
            ->exists();

        if ($existingMember) {
            Notification::make()
                ->danger()
                ->title('User is already a member of this distributor.')
                ->send();

            $this->halt();
        }

        $existingInvitation = Invitation::query()
            ->where('distributor_id', $tenant->id)
            ->where('email', $data['email'])
            ->whereNull('accepted_at')
            ->first();

        if ($existingInvitation) {
            $existingInvitation->update([
                'role' => $data['role'],
                'supplier_id' => $supplierId,
                'token' => Str::random(64),
                'expires_at' => now()->addHours(48),
                'invited_by' => Filament::auth()->id(),
            ]);

            Mail::to($existingInvitation->email)->send(new InvitationMail($existingInvitation->fresh(['distributor', 'inviter'])));

            Notification::make()
                ->success()
                ->title('Invitation updated and resent')
                ->send();

            return $existingInvitation;
        }

        $invitation = Invitation::create([
            'distributor_id' => $tenant->id,
            'supplier_id' => $supplierId,
            'email' => $data['email'],
            'role' => $data['role'],
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
            'invited_by' => Filament::auth()->id(),
        ]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation->fresh(['distributor', 'inviter'])));

        return $invitation;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSupplierId(Distributor $tenant, array $data): ?int
    {
        $role = Role::from($data['role']);

        if ($role !== Role::Supplier) {
            return null;
        }

        $supplierId = $data['supplier_id'] ?? null;

        if (! filled($supplierId)) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Select a supplier for supplier invitations.',
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
