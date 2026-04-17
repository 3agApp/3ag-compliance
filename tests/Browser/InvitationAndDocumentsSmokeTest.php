<?php

use App\Enums\Role;
use App\Models\Brand;
use App\Models\Distributor;
use App\Models\Invitation;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;

it('renders the invitation confirmation page without browser errors', function () {
    $distributor = Distributor::factory()->create(['name' => 'Acme Distribution']);
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $invitation = Invitation::factory()->create([
        'distributor_id' => $distributor->id,
        'email' => $user->email,
    ]);

    visit(URL::temporarySignedRoute('invitation.accept', $invitation->expires_at, [
        'token' => $invitation->token,
    ]))
        ->assertSee('Accept Invitation')
        ->assertSee('Acme Distribution')
        ->assertSee('invitee@example.com')
        ->assertNoSmoke();
});

it('renders the product documents UI without browser errors', function () {
    $distributor = Distributor::factory()->create(['slug' => 'acme-corp']);
    $owner = User::factory()->create();

    $distributor->members()->attach($owner, ['role' => Role::Owner->value]);

    $supplier = Supplier::factory()->create([
        'distributor_id' => $distributor->id,
    ]);

    $brand = Brand::factory()->create([
        'distributor_id' => $distributor->id,
        'supplier_id' => $supplier->id,
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $distributor->id,
        'supplier_id' => $supplier->id,
        'brand_id' => $brand->id,
    ]);

    $this->actingAs($owner);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($distributor);

    visit(route('filament.dashboard.resources.products.edit', [
        'tenant' => $distributor,
        'record' => $product,
    ], absolute: false))
        ->assertSee('Documents')
        ->assertSee('No documents found')
        ->assertNoSmoke();
});
