<?php

use App\Enums\DocumentType;
use App\Enums\Role;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Jobs\RunProductDocumentAnalysis;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentAnalysisRun;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use App\Notifications\ProductDocumentAnalysisCompleted;
use App\Services\Documents\ProductDocumentAnalysisService;
use App\Services\Products\ProductComponentDetector;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;

beforeEach(function () {
    config()->set('media-library.disk_name', 'local');
    config()->set('ai.providers.openai.key', 'test-key');
    config()->set('document-analysis.ai.provider', 'openai');
    config()->set('document-analysis.ai.model', 'gpt-5.4-nano');
    config()->set('document-analysis.batch_size', 6);

    Storage::fake('local');

    $this->distributor = Distributor::factory()->create(['slug' => 'acme-corp']);
    $this->owner = User::factory()->create();
    $this->supplier = Supplier::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);
    $this->brand = Brand::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $this->category = Category::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);
    $this->template = Template::factory()->create([
        'distributor_id' => $this->distributor->id,
        'category_id' => $this->category->id,
    ]);
    $this->product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $this->category->id,
        'template_id' => $this->template->id,
    ]);

    $this->distributor->members()->attach($this->owner, ['role' => Role::Owner->value]);

    $this->actingAs($this->owner);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($this->distributor);
});

it('starts a queued analysis run from the edit page', function () {
    Document::factory()->withFile('manual.pdf')->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $this->product->id,
        'type' => DocumentType::Manual,
    ]);

    Queue::fake();

    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertActionVisible('analyzeDocuments')
        ->callAction('analyzeDocuments')
        ->assertNotified();

    $run = DocumentAnalysisRun::query()->first();

    expect($run)->not->toBeNull()
        ->and($run?->status)->toBe(DocumentAnalysisRun::STATUS_QUEUED)
        ->and($run?->total_documents)->toBe(1)
        ->and($run?->total_batches)->toBe(1);

    Queue::assertPushed(RunProductDocumentAnalysis::class);
});

it('processes all documents in batches and creates components without duplicates on rerun', function () {
    Notification::fake();

    $documents = collect([
        ['name' => 'WiFi_Module_FCC_Certificate.pdf', 'type' => DocumentType::Certificate],
        ['name' => 'battery_report.pdf', 'type' => DocumentType::TestReport],
        ['name' => 'manual.pdf', 'type' => DocumentType::Manual],
        ['name' => 'charger_assessment.pdf', 'type' => DocumentType::Certificate],
        ['name' => 'display_emc.pdf', 'type' => DocumentType::RegulatoryDocument],
        ['name' => 'speaker_test.pdf', 'type' => DocumentType::TestReport],
        ['name' => 'usb_kabel_certificate.pdf', 'type' => DocumentType::Certificate],
    ])->map(function (array $payload): Document {
        return Document::factory()->withFile($payload['name'])->create([
            'distributor_id' => $this->distributor->id,
            'product_id' => $this->product->id,
            'type' => $payload['type'],
        ]);
    });

    $analysisService = Mockery::mock(ProductDocumentAnalysisService::class, [app(ProductComponentDetector::class)])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $analysisService->shouldReceive('analyzeBatch')->once()->andReturn([
        'batch_summary' => 'First batch done.',
        'documents' => $documents->take(6)->values()->map(fn (Document $document, int $index): array => [
            'document_id' => $document->id,
            'score' => 95 - ($index * 5),
            'rating' => 'compliant',
            'summary' => 'Batch 1 summary for '.$document->id,
            'findings' => ['Finding for '.$document->id],
        ])->all(),
    ]);
    $analysisService->shouldReceive('analyzeBatch')->once()->andReturn([
        'batch_summary' => 'Second batch done.',
        'documents' => [[
            'document_id' => $documents->last()->id,
            'score' => 72,
            'rating' => 'warning',
            'summary' => 'Batch 2 summary for '.$documents->last()->id,
            'findings' => ['Finding for '.$documents->last()->id],
        ]],
    ]);
    $analysisService->shouldReceive('analyzeBatch')->once()->andReturn([
        'batch_summary' => 'First rerun batch.',
        'documents' => $documents->take(6)->values()->map(fn (Document $document): array => [
            'document_id' => $document->id,
            'score' => 80,
            'rating' => 'warning',
            'summary' => 'Rerun summary for '.$document->id,
            'findings' => ['Rerun finding for '.$document->id],
        ])->all(),
    ]);
    $analysisService->shouldReceive('analyzeBatch')->once()->andReturn([
        'batch_summary' => 'Second rerun batch.',
        'documents' => [[
            'document_id' => $documents->last()->id,
            'score' => 82,
            'rating' => 'warning',
            'summary' => 'Rerun summary for '.$documents->last()->id,
            'findings' => ['Rerun finding for '.$documents->last()->id],
        ]],
    ]);

    app()->instance(ProductDocumentAnalysisService::class, $analysisService);

    $run = app(ProductDocumentAnalysisService::class)->start($this->product, $this->owner)->fresh();

    expect($run->status)->toBe(DocumentAnalysisRun::STATUS_COMPLETED)
        ->and($run->total_batches)->toBe(2)
        ->and($run->completed_batches)->toBe(2)
        ->and($run->result['document_count'])->toBe(7)
        ->and($run->result['documents'])->toHaveCount(7)
        ->and(ProductComponent::query()->count())->toBe(6);

    $wifiDocument = $documents->firstWhere('type', DocumentType::Certificate)->fresh();
    $batteryDocument = $documents->firstWhere('type', DocumentType::TestReport)->fresh();

    expect($wifiDocument->productComponent?->name)->toBe('WiFi Module')
        ->and($batteryDocument->productComponent?->name)->toBe('Battery');

    Notification::assertSentTo($this->owner, ProductDocumentAnalysisCompleted::class);

    $rerun = app(ProductDocumentAnalysisService::class)->start($this->product, $this->owner)->fresh();

    expect($rerun->status)->toBe(DocumentAnalysisRun::STATUS_COMPLETED)
        ->and(ProductComponent::query()->count())->toBe(6)
        ->and($rerun->detected_components)->toBe([]);
});

it('shows analysis progress on the edit page while a run is active', function () {
    DocumentAnalysisRun::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $this->product->id,
        'requested_by_user_id' => $this->owner->id,
        'status' => DocumentAnalysisRun::STATUS_RUNNING,
        'current_phase' => 'Analysing documents…',
        'batch_size' => 6,
        'total_documents' => 23,
        'processed_documents' => 6,
        'total_batches' => 4,
        'completed_batches' => 1,
        'started_at' => now(),
    ]);

    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertSee('AI document analysis')
        ->assertSee('Analysing documents…')
        ->assertSee('Batch 2 of 4');
});
