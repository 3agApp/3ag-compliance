<?php

use App\Enums\DocumentType;
use App\Enums\Role;
use App\Models\Brand;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create(['slug' => 'acme-corp']);
    $this->owner = User::factory()->create();

    $this->organization->members()->attach($this->owner, ['role' => Role::Owner->value]);

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
    ]);

    $this->document = Document::factory()->create([
        'product_id' => $this->product->id,
        'type' => DocumentType::Manual,
    ]);
});

test('a document belongs to a product and can keep multiple media files', function () {
    Storage::fake('public');
    config()->set('media-library.disk_name', 'public');

    $document = Document::factory()->create([
        'product_id' => $this->product->id,
        'type' => DocumentType::Certificate,
    ]);

    $firstFile = $document
        ->addMedia(UploadedFile::fake()->create('certificate.pdf', 128, 'application/pdf'))
        ->toMediaCollection(Document::FILE_COLLECTION);

    $secondFile = $document
        ->addMedia(UploadedFile::fake()->create('appendix.pdf', 256, 'application/pdf'))
        ->toMediaCollection(Document::FILE_COLLECTION);

    Media::setNewOrder([$secondFile->getKey(), $firstFile->getKey()]);

    $document->refresh();

    $fileNames = $document->getMedia(Document::FILE_COLLECTION)
        ->pluck('file_name')
        ->all();

    expect($document->product->is($this->product))->toBeTrue()
        ->and($document->type)->toBe(DocumentType::Certificate)
        ->and($document->getMedia(Document::FILE_COLLECTION))->toHaveCount(2)
        ->and($fileNames)->toBe(['appendix.pdf', 'certificate.pdf']);
});

dataset('product_document_filament_pages', [
    'documents index' => ['filament.dashboard.resources.products.documents.index'],
    'documents create' => ['filament.dashboard.resources.products.documents.create'],
    'documents edit' => ['filament.dashboard.resources.products.documents.edit'],
]);

test('loads each nested product document filament page', function (string $routeName) {
    $parameters = [
        'tenant' => $this->organization,
        'product' => $this->product,
    ];

    if ($routeName === 'filament.dashboard.resources.products.documents.edit') {
        $parameters['record'] = $this->document;
    }

    $this->actingAs($this->owner)
        ->get(route($routeName, $parameters))
        ->assertSuccessful();
})->with('product_document_filament_pages');
