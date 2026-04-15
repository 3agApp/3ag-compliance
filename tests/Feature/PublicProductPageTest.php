<?php

use App\Enums\ProductStatus;
use App\Models\Organization;
use App\Models\Product;

it('displays the public product page for a valid public uuid', function () {
    $organization = Organization::factory()->create();
    $product = Product::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Test Product Alpha',
        'status' => ProductStatus::Approved,
    ]);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertSee('Test Product Alpha')
        ->assertSee($organization->name)
        ->assertSee('Verified');
});

it('shows in-progress seal for products under review', function () {
    $product = Product::factory()->create([
        'organization_id' => Organization::factory(),
        'status' => ProductStatus::UnderReview,
    ]);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertSee('Verification in progress');
});

it('shows not-verified seal for open products', function () {
    $product = Product::factory()->create([
        'organization_id' => Organization::factory(),
        'status' => ProductStatus::Open,
        'completeness_score' => 0,
    ]);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertSee('Not verified');
});

it('returns 404 for an invalid public uuid', function () {
    $this->get('/p/non-existent-uuid')
        ->assertNotFound();
});
