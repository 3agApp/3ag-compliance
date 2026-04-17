<?php

use App\Enums\Role;
use App\Filament\Pages\Tenancy\EditDistributorProfile;
use App\Filament\Pages\Tenancy\RegisterDistributor;
use App\Models\Distributor;
use App\Models\Invitation;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('returns a users distributors as available tenants', function () {
    $distributor = Distributor::factory()->create();
    $otherDistributor = Distributor::factory()->create();
    $user = User::factory()->create();

    $distributor->members()->attach($user, ['role' => Role::Owner->value]);

    $panel = Filament::getPanel('dashboard');

    expect($user->getTenants($panel))
        ->toHaveCount(1)
        ->and($user->getTenants($panel)->first()->is($distributor))->toBeTrue()
        ->and($user->canAccessTenant($distributor))->toBeTrue()
        ->and($user->canAccessTenant($otherDistributor))->toBeFalse();
});

it('labels the active distributor in the tenant switcher', function () {
    $distributor = Distributor::factory()->create();

    expect($distributor->getCurrentTenantLabel())->toBe('Active Distributor');
});

it('redirects to the updated tenant profile url after changing the slug', function () {
    $distributor = Distributor::factory()->create(['slug' => 'old-slug']);
    $user = User::factory()->create();

    $distributor->members()->attach($user, ['role' => Role::Owner->value]);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($distributor);

    Livewire::test(EditDistributorProfile::class)
        ->set('data.name', 'Updated Distributor')
        ->set('data.slug', 'new-slug')
        ->call('save')
        ->assertRedirect(EditDistributorProfile::getUrl(tenant: $distributor->fresh()));

    expect($distributor->fresh()->slug)->toBe('new-slug');
});

it('shows pending invitations instead of the distributor form on tenant registration', function () {
    $distributor = Distributor::factory()->create(['name' => 'Inviting Distributor']);
    $user = User::factory()->create(['email' => 'invitee@example.com']);

    Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => $user->email,
    ]);

    $this->actingAs($user)
        ->get(route('filament.dashboard.tenant.registration'))
        ->assertSuccessful()
        ->assertSee('Pending Invitations')
        ->assertSee('Inviting Distributor')
        ->assertSee('Register a Distributor Instead');
});

it('accepts a pending invitation from the tenant registration page', function () {
    $distributor = Distributor::factory()->create(['slug' => 'invite-org']);
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => $user->email,
    ]);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));

    Livewire::test(RegisterDistributor::class)
        ->call('acceptInvitation', $invitation->id)
        ->assertRedirect(route('filament.dashboard.pages.dashboard', ['tenant' => $distributor->slug]));

    expect($user->fresh()->distributors()->whereKey($distributor)->exists())->toBeTrue()
        ->and($invitation->fresh()->isAccepted())->toBeTrue();
});

it('rejects a pending invitation from the tenant registration page', function () {
    $distributor = Distributor::factory()->create();
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => $user->email,
    ]);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));

    Livewire::test(RegisterDistributor::class)
        ->call('rejectInvitation', $invitation->id)
        ->assertSet('showRegistrationForm', true);

    expect($invitation->fresh())->toBeNull();
});

it('lets invited users continue to the distributor form explicitly', function () {
    $distributor = Distributor::factory()->create();
    $user = User::factory()->create(['email' => 'invitee@example.com']);

    Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => $user->email,
    ]);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));

    Livewire::test(RegisterDistributor::class)
        ->call('showDistributorRegistrationForm')
        ->assertSet('showRegistrationForm', true)
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('slug');
});
