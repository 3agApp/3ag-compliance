<?php

use App\Enums\Role;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->member = User::factory()->create();
    $this->outsider = User::factory()->create();

    $this->organization->members()->attach($this->owner, ['role' => Role::Owner->value]);
    $this->organization->members()->attach($this->admin, ['role' => Role::Admin->value]);
    $this->organization->members()->attach($this->member, ['role' => Role::Member->value]);

    $this->actingAs($this->owner);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($this->organization);
});

describe('OrganizationPolicy', function () {
    it('allows any user to view any organizations', function () {
        expect($this->owner->can('viewAny', Organization::class))->toBeTrue();
    });

    it('allows members to view their organization', function () {
        expect($this->owner->can('view', $this->organization))->toBeTrue()
            ->and($this->member->can('view', $this->organization))->toBeTrue();
    });

    it('prevents outsiders from viewing an organization', function () {
        expect($this->outsider->can('view', $this->organization))->toBeFalse();
    });

    it('allows owner and admin to update organization', function () {
        expect($this->owner->can('update', $this->organization))->toBeTrue()
            ->and($this->admin->can('update', $this->organization))->toBeTrue()
            ->and($this->member->can('update', $this->organization))->toBeFalse();
    });
});

describe('InvitationPolicy', function () {
    it('allows owner and admin to manage invitations', function () {
        $invitation = Invitation::factory()->create(['organization_id' => $this->organization->id]);

        expect($this->owner->can('viewAny', Invitation::class))->toBeTrue()
            ->and($this->owner->can('create', Invitation::class))->toBeTrue()
            ->and($this->owner->can('delete', $invitation))->toBeTrue()
            ->and($this->admin->can('viewAny', Invitation::class))->toBeTrue()
            ->and($this->admin->can('create', Invitation::class))->toBeTrue();
    });

    it('prevents members from managing invitations', function () {
        $invitation = Invitation::factory()->create(['organization_id' => $this->organization->id]);

        expect($this->member->can('viewAny', Invitation::class))->toBeFalse()
            ->and($this->member->can('create', Invitation::class))->toBeFalse()
            ->and($this->member->can('delete', $invitation))->toBeFalse();
    });
});

describe('Role enum', function () {
    it('exposes the CPM member permissions', function () {
        expect(Role::Owner->canManageMembers())->toBeTrue()
            ->and(Role::Admin->canManageMembers())->toBeTrue()
            ->and(Role::Member->canManageMembers())->toBeFalse()
            ->and(Role::Owner->canManageOrganization())->toBeTrue()
            ->and(Role::Admin->canManageOrganization())->toBeTrue()
            ->and(Role::Member->canManageOrganization())->toBeFalse()
            ->and(Role::Owner->canDeleteOrganization())->toBeTrue()
            ->and(Role::Admin->canDeleteOrganization())->toBeFalse()
            ->and(Role::Member->canDeleteOrganization())->toBeFalse();
    });
});
