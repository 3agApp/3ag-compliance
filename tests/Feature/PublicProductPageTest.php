<?php

use App\Enums\DocumentType;
use App\Enums\ProductStatus;
use App\Models\Document;
use App\Models\Product;
use App\Models\ProductSafetyEntry;
use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('displays the public product page by uuid', function () {
    $product = Product::factory()->create(['name' => 'Test Widget']);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/public/show')
            ->where('product.name', 'Test Widget')
            ->where('product.public_uuid', $product->public_uuid)
        );
});

it('returns 404 for an invalid uuid', function () {
    $this->get('/p/00000000-0000-0000-0000-000000000000')
        ->assertNotFound();
});

it('does not require authentication', function () {
    $product = Product::factory()->create();

    $this->assertGuest();

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful();
});

it('includes only public_download documents', function () {
    Storage::fake('public');

    $product = Product::factory()->create();

    $publicDoc = Document::factory()->for($product)->publicDownload()->create([
        'type' => DocumentType::Certificate,
    ]);
    $publicDoc->addMedia(
        UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf')
    )->toMediaCollection('file');

    $privateDoc = Document::factory()->for($product)->create([
        'type' => DocumentType::TestReport,
        'public_download' => false,
    ]);
    $privateDoc->addMedia(
        UploadedFile::fake()->create('test-report.pdf', 100, 'application/pdf')
    )->toMediaCollection('file');

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('product.documents', 1)
            ->where('product.documents.0.type', DocumentType::Certificate->value)
        );
});

it('excludes non-current documents even if public_download is true', function () {
    $product = Product::factory()->create();

    Document::factory()->for($product)->create([
        'type' => DocumentType::Manual,
        'public_download' => true,
        'is_current' => false,
    ]);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('product.documents', 0)
        );
});

it('includes safety entry data', function () {
    $product = Product::factory()->create();
    ProductSafetyEntry::factory()->for($product)->create([
        'warning_text' => 'Not suitable for children under 3.',
        'age_grading' => '3+',
    ]);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('product.safety_entry.warning_text', 'Not suitable for children under 3.')
            ->where('product.safety_entry.age_grading', '3+')
        );
});

it('includes product images', function () {
    Storage::fake('public');

    $product = Product::factory()->create();
    $product->addMedia(
        UploadedFile::fake()->image('product.jpg', 800, 800)
    )->toMediaCollection('images');

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('product.images', 1)
            ->where('product.images.0.name', 'product.jpg')
        );
});

it('shows correct seal status', function () {
    $product = Product::factory()->create(['status' => ProductStatus::Approved]);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('product.seal_status', 'verified')
        );
});

it('includes supplier and brand names', function () {
    $supplier = Supplier::factory()->create(['name' => 'Safe Toys Inc.']);
    $product = Product::factory()->create(['supplier_id' => $supplier->id]);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('product.supplier.name', 'Safe Toys Inc.')
        );
});

it('does not expose internal product data', function () {
    $product = Product::factory()->create();

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('product.id')
            ->missing('product.status')
            ->missing('product.completeness_score')
            ->missing('product.template_id')
        );
});
