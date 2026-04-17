<?php

use App\Enums\Role;
use App\Filament\Pages\Auth\Register as RegisterPage;
use App\Models\Distributor;
use App\Models\Invitation;
use App\Models\Supplier;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

it('detects pending expired and accepted invitations', function () {
    $pending = Invitation::factory()->create();
    $expired = Invitation::factory()->expired()->create();
    $accepted = Invitation::factory()->accepted()->create();

    expect($pending->isPending())->toBeTrue()
        ->and($pending->isAccepted())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue()
        ->and($accepted->isAccepted())->toBeTrue();
});

it('shows a confirmation screen for an existing invited user before accepting', function () {
    $distributor = Distributor::factory()->create();
    $user = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => $user->email,
        'role' => Role::Admin,
    ]);

    $response = $this->get(URL::temporarySignedRoute('invitation.accept', $invitation->expires_at, [
        'token' => $invitation->token,
    ]));

    $response->assertSuccessful()
        ->assertSee('Accept Invitation')
        ->assertSee($distributor->name)
        ->assertSee($invitation->email);

    expect($user->fresh()->distributors()->whereKey($distributor)->exists())->toBeFalse()
        ->and($invitation->fresh()->isAccepted())->toBeFalse();
});

it('accepts invitation for existing user after confirmation', function () {
    $distributor = Distributor::factory()->create();
    $user = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => $user->email,
        'role' => Role::Admin,
    ]);

    $this->get(URL::temporarySignedRoute('invitation.accept', $invitation->expires_at, [
        'token' => $invitation->token,
    ]))->assertSuccessful();

    $response = $this->post(route('invitation.accept.confirm', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));
    $response->assertSessionHas('filament.notifications.0.title', 'Invitation accepted.');

    expect($user->fresh()->distributors()->whereKey($distributor)->exists())->toBeTrue()
        ->and($user->fresh()->getRoleForDistributor($distributor))->toBe(Role::Admin)
        ->and($invitation->fresh()->isAccepted())->toBeTrue();
});

it('accepts a supplier-scoped invitation for an existing user', function () {
    $distributor = Distributor::factory()->create();
    $supplier = Supplier::factory()->create(['distributor_id' => $distributor->id]);
    $user = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'supplier_id' => $supplier->id,
        'email' => $user->email,
        'role' => Role::Supplier,
    ]);

    $this->get(URL::temporarySignedRoute('invitation.accept', $invitation->expires_at, [
        'token' => $invitation->token,
    ]))->assertSuccessful();

    $response = $this->post(route('invitation.accept.confirm', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));

    expect($user->fresh()->getRoleForDistributor($distributor))->toBe(Role::Supplier)
        ->and($user->fresh()->getSupplierIdForDistributor($distributor))->toBe($supplier->id)
        ->and($invitation->fresh()->isAccepted())->toBeTrue();
});

it('redirects authenticated invited users to their tenant dashboard', function () {
    $distributor = Distributor::factory()->create();
    $user = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => $user->email,
    ]);

    $this->actingAs($user)
        ->get(URL::temporarySignedRoute('invitation.accept', $invitation->expires_at, [
            'token' => $invitation->token,
        ]))
        ->assertSuccessful();

    $response = $this->actingAs($user)->post(route('invitation.accept.confirm', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.pages.dashboard', ['tenant' => $distributor->slug]));
    $response->assertSessionHas('filament.notifications.0.title', 'Invitation accepted.');
});

it('redirects new users to register for invitation acceptance', function () {
    $invitation = Invitation::factory()->create([
        'email' => 'newuser@example.com',
    ]);

    $this->get(URL::temporarySignedRoute('invitation.accept', $invitation->expires_at, [
        'token' => $invitation->token,
    ]))->assertSuccessful();

    $response = $this->post(route('invitation.accept.confirm', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.register'));
    $response->assertSessionHas('filament.notifications.0.title', 'Please create an account to join the distributor.');

    expect(session('pending_invitation_token'))->toBe($invitation->token);
});

it('rejects expired invitation', function () {
    $invitation = Invitation::factory()->expired()->create();

    $response = $this->get(URL::temporarySignedRoute('invitation.accept', now()->subMinute(), [
        'token' => $invitation->token,
    ]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));
    $response->assertSessionHas('filament.notifications.0.title', 'This invitation link is invalid or has expired.');
});

it('rejects already accepted invitation', function () {
    $invitation = Invitation::factory()->accepted()->create();

    $response = $this->get(URL::temporarySignedRoute('invitation.accept', now()->addHour(), [
        'token' => $invitation->token,
    ]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));
    $response->assertSessionHas('filament.notifications.0.title', 'This invitation has already been accepted.');
});

it('does not accept an invitation while signed in as a different user', function () {
    $distributor = Distributor::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $currentUser = User::factory()->create(['email' => 'current@example.com']);
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => $invitedUser->email,
    ]);

    $this->actingAs($currentUser)
        ->get(URL::temporarySignedRoute('invitation.accept', $invitation->expires_at, [
            'token' => $invitation->token,
        ]))
        ->assertSuccessful();

    $response = $this->actingAs($currentUser)->post(route('invitation.accept.confirm', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));
    $response->assertSessionHas('filament.notifications.0.title', 'Sign in with the invited account to accept this invitation.');

    expect($invitedUser->fresh()->distributors()->whereKey($distributor)->exists())->toBeFalse()
        ->and($invitation->fresh()->isAccepted())->toBeFalse();
});

it('accepts a pending invitation after matching registration', function () {
    Filament::setCurrentPanel('dashboard');

    $distributor = Distributor::factory()->create();
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => 'newuser@example.com',
    ]);

    session(['pending_invitation_token' => $invitation->token]);

    Livewire::test(RegisterPage::class)
        ->set('data.name', 'New User')
        ->set('data.email', $invitation->email)
        ->set('data.password', 'password')
        ->set('data.passwordConfirmation', 'password')
        ->call('register')
        ->assertRedirect(route('filament.dashboard.tenant.registration'));

    $user = User::where('email', $invitation->email)->first();

    expect($user)->not->toBeNull()
        ->and($user->distributors()->whereKey($distributor)->exists())->toBeFalse()
        ->and($invitation->fresh()->isAccepted())->toBeFalse()
        ->and(session('pending_invitation_token'))->toBe($invitation->token);
});

it('accepts a supplier-scoped invitation after matching registration', function () {
    Filament::setCurrentPanel('dashboard');

    $distributor = Distributor::factory()->create();
    $supplier = Supplier::factory()->create(['distributor_id' => $distributor->id]);
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'supplier_id' => $supplier->id,
        'email' => 'supplier-user@example.com',
        'role' => Role::Supplier,
    ]);

    session(['pending_invitation_token' => $invitation->token]);

    Livewire::test(RegisterPage::class)
        ->set('data.name', 'Supplier User')
        ->set('data.email', $invitation->email)
        ->set('data.password', 'password')
        ->set('data.passwordConfirmation', 'password')
        ->call('register')
        ->assertRedirect(route('filament.dashboard.tenant.registration'));

    $user = User::where('email', $invitation->email)->first();

    expect($user)->not->toBeNull()
        ->and($user->getRoleForDistributor($distributor))->toBeNull()
        ->and($user->getSupplierIdForDistributor($distributor))->toBeNull()
        ->and($invitation->fresh()->isAccepted())->toBeFalse()
        ->and(session('pending_invitation_token'))->toBe($invitation->token);
});

it('keeps the pending invitation when registration email does not match', function () {
    Filament::setCurrentPanel('dashboard');

    $invitation = Invitation::factory()->create([
        'email' => 'invited@example.com',
    ]);

    session(['pending_invitation_token' => $invitation->token]);

    Livewire::test(RegisterPage::class)
        ->set('data.name', 'Wrong User')
        ->set('data.email', 'wrong@example.com')
        ->set('data.password', 'password')
        ->set('data.passwordConfirmation', 'password')
        ->call('register');

    expect($invitation->fresh()->isAccepted())->toBeFalse()
        ->and(session('pending_invitation_token'))->toBeNull()
        ->and(User::where('email', 'wrong@example.com')->exists())->toBeTrue();
});

it('does not accept an invitation without first opening the signed confirmation page', function () {
    $distributor = Distributor::factory()->create();
    $user = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => $user->email,
    ]);

    $response = $this->post(route('invitation.accept.confirm', ['token' => $invitation->token]));

    $response->assertRedirect(route('filament.dashboard.auth.login'));
    $response->assertSessionHas('filament.notifications.0.title', 'Open the invitation link again before confirming.');

    expect($user->fresh()->distributors()->whereKey($distributor)->exists())->toBeFalse()
        ->and($invitation->fresh()->isAccepted())->toBeFalse();
});
