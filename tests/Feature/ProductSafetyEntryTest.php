<?php

use App\Models\Product;
use App\Models\ProductSafetyEntry;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create a safety entry via factory', function () {
    $product = Product::factory()->create();
    $entry = ProductSafetyEntry::factory()->create(['product_id' => $product->id]);

    expect($entry)
        ->product_id->toBe($product->id)
        ->and($entry->product->id)->toBe($product->id);
});

it('belongs to a product', function () {
    $product = Product::factory()->create();
    $entry = ProductSafetyEntry::factory()->create(['product_id' => $product->id]);

    expect($entry->product)->toBeInstanceOf(Product::class);
});

it('has nullable safety fields', function () {
    $product = Product::factory()->create();
    $entry = ProductSafetyEntry::factory()->create([
        'product_id' => $product->id,
        'safety_text' => null,
        'warning_text' => null,
        'age_grading' => null,
        'material_information' => null,
        'usage_restrictions' => null,
        'safety_instructions' => null,
        'additional_notes' => null,
    ]);

    expect($entry->safety_text)->toBeNull()
        ->and($entry->warning_text)->toBeNull()
        ->and($entry->age_grading)->toBeNull()
        ->and($entry->material_information)->toBeNull()
        ->and($entry->usage_restrictions)->toBeNull()
        ->and($entry->safety_instructions)->toBeNull()
        ->and($entry->additional_notes)->toBeNull();
});

it('cascades delete when product is deleted', function () {
    $product = Product::factory()->create();
    ProductSafetyEntry::factory()->create(['product_id' => $product->id]);

    $product->delete();

    $this->assertDatabaseMissing('product_safety_entries', ['product_id' => $product->id]);
});

it('creates a safety entry via the update endpoint', function () {
    $product = Product::factory()->create();

    $this->putJson(route('products.safety-entry.update', $product), [
        'safety_text' => 'Keep away from fire',
        'warning_text' => 'Choking hazard',
    ])->assertSuccessful();

    $this->assertDatabaseHas('product_safety_entries', [
        'product_id' => $product->id,
        'safety_text' => 'Keep away from fire',
        'warning_text' => 'Choking hazard',
    ]);
});

it('updates an existing safety entry via the update endpoint', function () {
    $product = Product::factory()->create();
    ProductSafetyEntry::factory()->create([
        'product_id' => $product->id,
        'safety_text' => 'Old text',
    ]);

    $this->putJson(route('products.safety-entry.update', $product), [
        'safety_text' => 'New text',
        'warning_text' => 'Updated warning',
    ])->assertSuccessful();

    expect($product->safetyEntry->fresh())
        ->safety_text->toBe('New text')
        ->warning_text->toBe('Updated warning');
});

it('validates max length on safety entry fields', function () {
    $product = Product::factory()->create();

    $this->putJson(route('products.safety-entry.update', $product), [
        'safety_text' => str_repeat('x', 5001),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['safety_text']);
});

it('allows all fields to be null', function () {
    $product = Product::factory()->create();

    $this->putJson(route('products.safety-entry.update', $product), [
        'safety_text' => null,
        'warning_text' => null,
        'age_grading' => null,
        'material_information' => null,
        'usage_restrictions' => null,
        'safety_instructions' => null,
        'additional_notes' => null,
    ])->assertSuccessful();

    $this->assertDatabaseHas('product_safety_entries', [
        'product_id' => $product->id,
        'safety_text' => null,
    ]);
});

it('redirects guests from safety entry endpoint', function () {
    auth()->logout();

    $product = Product::factory()->create();

    $this->putJson(route('products.safety-entry.update', $product), [
        'safety_text' => 'test',
    ])->assertUnauthorized();
});

it('includes safety entry in product edit page', function () {
    $product = Product::factory()->create();
    ProductSafetyEntry::factory()->create([
        'product_id' => $product->id,
        'safety_text' => 'Important safety info',
    ]);

    $this->get(route('products.edit', $product))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('products/edit')
            ->where('product.safety_entry.safety_text', 'Important safety info')
        );
});
