<?php

use App\Enums\Role;
use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function productResourceContext(): array
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->members()->attach($user, ['role' => Role::Owner->value]);

    test()->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($organization);

    return [$organization, $user];
}

it('lists only products from the current tenant', function () {
    [$organization] = productResourceContext();

    $visibleProduct = Product::factory()->create([
        'organization_id' => $organization->id,
        'title' => 'Visible tenant product',
    ]);

    Product::factory()->create([
        'title' => 'Hidden tenant product',
    ]);

    Livewire::test(ManageProducts::class)
        ->assertCanSeeTableRecords([$visibleProduct])
        ->assertCountTableRecords(1);
});

it('associates created products with the active tenant', function () {
    [$organization] = productResourceContext();

    Livewire::test(ManageProducts::class)
        ->callAction('create', data: [
            'title' => 'Created inside tenant',
        ]);

    expect(Product::query()->count())->toBe(1)
        ->and(Product::query()->first()?->organization?->is($organization))->toBeTrue()
        ->and(Product::query()->first()?->title)->toBe('Created inside tenant');
});
