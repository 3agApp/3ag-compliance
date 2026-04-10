<?php

use App\Enums\DocumentType;
use App\Enums\ProductStatus;
use App\Enums\SealStatus;
use App\Models\Document;
use App\Models\Product;
use App\Models\ProductSafetyEntry;
use App\Models\Template;
use App\Models\User;
use App\Services\CompletenessScoreCalculator;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->calculator = app(CompletenessScoreCalculator::class);
});

it('returns 100 when template has no required documents', function () {
    $product = Product::factory()
        ->for(Template::factory()->state([
            'required_document_types' => [],
        ]))
        ->create();

    $score = $this->calculator->calculate($product);

    expect($score)->toBe(100.0);
    expect($product->fresh()->completeness_score)->toEqual('100.00');
});

it('returns 0 when no documents are provided and no safety data', function () {
    $product = Product::factory()
        ->for(Template::factory()->state([
            'required_document_types' => [DocumentType::TestReport->value],
        ]))
        ->create();

    $score = $this->calculator->calculate($product);

    expect($score)->toBe(0.0);
});

it('calculates score based on document weights', function () {
    $template = Template::factory()->state([
        'required_document_types' => [
            DocumentType::TestReport->value,           // weight 3
            DocumentType::Certificate->value,          // weight 2
            DocumentType::Manual->value,               // weight 1
        ],
    ])->create();

    $product = Product::factory()->for($template)->create();

    Document::factory()->create([
        'product_id' => $product->id,
        'type' => DocumentType::TestReport,
        'is_current' => true,
    ]);

    $score = $this->calculator->calculate($product);

    // Doc total weight = 3 + 2 + 1 = 6, earned = 3
    // Safety weight = max(3, round(6 * 0.25)) = 3, earned = 0
    // Total: 3 / (6 + 3) = 33.33%
    expect($score)->toBe(33.33);
});

it('gives higher weight to high priority documents', function () {
    $template = Template::factory()->state([
        'required_document_types' => [
            DocumentType::TestReport->value,              // weight 3
            DocumentType::DeclarationOfConformity->value, // weight 3
            DocumentType::Other->value,                   // weight 1
        ],
    ])->create();

    $product = Product::factory()->for($template)->create();

    Document::factory()->create([
        'product_id' => $product->id,
        'type' => DocumentType::TestReport,
        'is_current' => true,
    ]);
    Document::factory()->create([
        'product_id' => $product->id,
        'type' => DocumentType::DeclarationOfConformity,
        'is_current' => true,
    ]);

    $score = $this->calculator->calculate($product);

    // Doc total = 3 + 3 + 1 = 7, earned = 6
    // Safety weight = max(3, round(7 * 0.25)) = 3, earned = 0
    // Total = 6 / (7 + 3) = 60%
    expect($score)->toBe(60.0);
});

it('includes safety data in score calculation', function () {
    $template = Template::factory()->state([
        'required_document_types' => [
            DocumentType::TestReport->value, // weight 3
        ],
    ])->create();

    $product = Product::factory()->for($template)->create();

    ProductSafetyEntry::factory()->create([
        'product_id' => $product->id,
        'safety_text' => 'Safe for use',
        'warning_text' => 'Keep away from fire',
        'age_grading' => '3+',
    ]);

    $score = $this->calculator->calculate($product);

    // Doc weight = 3, earned = 0
    // Safety weight = max(3, round(3 * 0.25)) = 3, earned = 3
    // Total = 3 / (3 + 3) = 50%
    expect($score)->toBe(50.0);
});

it('gives partial safety score for partially filled fields', function () {
    $template = Template::factory()->state([
        'required_document_types' => [
            DocumentType::Manual->value, // weight 1
        ],
    ])->create();

    $product = Product::factory()->for($template)->create();

    ProductSafetyEntry::factory()->create([
        'product_id' => $product->id,
        'safety_text' => 'Safe',
        'warning_text' => null,
        'age_grading' => null,
    ]);

    $score = $this->calculator->calculate($product);

    // Doc weight = 1, earned = 0
    // Safety weight = max(3, round(1 * 0.25)) = 3, earned = 1/3 * 3 = 1
    // Total = 1 / (1 + 3) = 25%
    expect($score)->toBe(25.0);
});

it('calculates 100 when all documents provided and all safety filled', function () {
    $template = Template::factory()->state([
        'required_document_types' => [
            DocumentType::TestReport->value,
            DocumentType::Certificate->value,
        ],
    ])->create();

    $product = Product::factory()->for($template)->create();

    Document::factory()->create([
        'product_id' => $product->id,
        'type' => DocumentType::TestReport,
        'is_current' => true,
    ]);
    Document::factory()->create([
        'product_id' => $product->id,
        'type' => DocumentType::Certificate,
        'is_current' => true,
    ]);

    ProductSafetyEntry::factory()->create([
        'product_id' => $product->id,
        'safety_text' => 'Safe',
        'warning_text' => 'Warning',
        'age_grading' => '6+',
    ]);

    $score = $this->calculator->calculate($product);

    expect($score)->toBe(100.0);
});

it('only counts current documents not replaced ones', function () {
    $template = Template::factory()->state([
        'required_document_types' => [
            DocumentType::TestReport->value,
        ],
    ])->create();

    $product = Product::factory()->for($template)->create();

    Document::factory()->create([
        'product_id' => $product->id,
        'type' => DocumentType::TestReport,
        'is_current' => false,
    ]);

    $score = $this->calculator->calculate($product);

    // Doc weight = 3, earned = 0 (not current)
    // Safety weight = 3, earned = 0
    // Total = 0 / 6 = 0%
    expect($score)->toBe(0.0);
});

it('persists the score to the database', function () {
    $template = Template::factory()->state([
        'required_document_types' => [DocumentType::Manual->value],
    ])->create();

    $product = Product::factory()->for($template)->create();

    Document::factory()->create([
        'product_id' => $product->id,
        'type' => DocumentType::Manual,
        'is_current' => true,
    ]);

    $this->calculator->calculate($product);

    // Doc weight = 1, earned = 1; Safety weight = 3, earned = 0
    // Total = 1 / 4 = 25%
    expect($product->fresh()->completeness_score)->toEqual('25.00');
});

// --- Seal Status Tests ---

it('returns verified seal status when product is approved', function () {
    $product = Product::factory()->create(['status' => ProductStatus::Approved]);

    expect($product->sealStatus())->toBe(SealStatus::Verified);
});

it('returns in_progress seal status when score is greater than 0', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::Open,
        'completeness_score' => 25.0,
    ]);

    expect($product->sealStatus())->toBe(SealStatus::InProgress);
});

it('returns in_progress seal status when status is not open', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::InProgress,
        'completeness_score' => 0,
    ]);

    expect($product->sealStatus())->toBe(SealStatus::InProgress);
});

it('returns not_verified seal status for open product with zero score', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::Open,
        'completeness_score' => 0,
    ]);

    expect($product->sealStatus())->toBe(SealStatus::NotVerified);
});

it('respects seal_status_override', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::Open,
        'completeness_score' => 0,
        'seal_status_override' => SealStatus::Verified,
    ]);

    expect($product->sealStatus())->toBe(SealStatus::Verified);
});

// --- Controller Integration Tests ---

it('recalculates score when safety entry is updated', function () {
    $template = Template::factory()->state([
        'required_document_types' => [DocumentType::TestReport->value],
    ])->create();

    $product = Product::factory()->for($template)->create();

    $this->putJson(route('products.safety-entry.update', $product), [
        'safety_text' => 'Safe for children',
        'warning_text' => 'Do not eat',
        'age_grading' => '3+',
    ])->assertSuccessful()
        ->assertJsonStructure(['safety_entry', 'completeness_score']);

    expect($product->fresh()->completeness_score)->toBeGreaterThan(0);
});

it('recalculates score when document is uploaded', function () {
    $template = Template::factory()->state([
        'required_document_types' => [DocumentType::TestReport->value],
    ])->create();

    $product = Product::factory()->for($template)->create();

    $this->postJson(route('products.documents.store', $product), [
        'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        'type' => DocumentType::TestReport->value,
        'expiry_date' => '',
        'review_comment' => '',
        'duplicate_strategy' => 'add_new',
    ])->assertSuccessful()
        ->assertJsonStructure(['documents', 'completeness_score']);

    expect($product->fresh()->completeness_score)->toBeGreaterThan(0);
});
