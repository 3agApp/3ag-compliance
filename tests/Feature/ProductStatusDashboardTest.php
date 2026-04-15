<?php

use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Brand;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->owner = User::factory()->create();
    $this->supplier = Supplier::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->brand = Brand::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $this->product = Product::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'status' => ProductStatus::UnderReview,
    ]);

    $this->organization->members()->attach($this->owner, ['role' => Role::Owner->value]);

    $this->actingAs($this->owner);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($this->organization);
});

it('does not expose a status field on the create product page', function () {
    Livewire::test(CreateProduct::class)
        ->assertFormFieldDoesNotExist('status');
});

it('does not expose a status field on the edit product page', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertFormFieldDoesNotExist('status');
});

it('ignores dashboard attempts to change product status when editing', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->set('data.name', 'Updated Product Name')
        ->set('data.status', ProductStatus::Rejected->value)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->product->fresh()->name)->toBe('Updated Product Name')
        ->and($this->product->fresh()->status)->toBe(ProductStatus::UnderReview);
});

it('normalizes removed product statuses before the enum cast is used', function () {
    $submittedProduct = Product::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
    ]);
    $completedProduct = Product::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
    ]);

    DB::table('products')
        ->where('id', $submittedProduct->getKey())
        ->update(['status' => 'submitted']);

    DB::table('products')
        ->where('id', $completedProduct->getKey())
        ->update(['status' => 'completed']);

    $migration = require database_path('migrations/2026_04_15_090801_normalize_removed_product_statuses_on_products_table.php');

    $migration->up();

    expect(DB::table('products')->where('id', $submittedProduct->getKey())->value('status'))->toBe(ProductStatus::UnderReview->value)
        ->and(DB::table('products')->where('id', $completedProduct->getKey())->value('status'))->toBe(ProductStatus::Approved->value)
        ->and(Product::query()->findOrFail($submittedProduct->getKey())->status)->toBe(ProductStatus::UnderReview)
        ->and(Product::query()->findOrFail($completedProduct->getKey())->status)->toBe(ProductStatus::Approved);
});
