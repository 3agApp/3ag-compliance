<?php

namespace App\Services\Documents;

use App\Ai\ProductDocumentBatchAgent;
use App\Jobs\RunProductDocumentAnalysis;
use App\Models\Document;
use App\Models\DocumentAnalysisRun;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductDocumentAnalysisCompleted;
use App\Services\Products\ProductComponentDetector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Files\Document as AiDocumentFile;
use Laravel\Ai\Files\StoredDocument;
use LogicException;

class ProductDocumentAnalysisService
{
    public function __construct(
        protected ProductComponentDetector $componentDetector,
    ) {}

    public function start(Product $product, ?User $requestedBy = null): DocumentAnalysisRun
    {
        $this->assertAiConfiguration();

        $product->loadMissing('documents.media');

        $documents = $this->analyzableDocuments($product);

        if ($documents->isEmpty()) {
            throw new LogicException('Add at least one uploaded document file before running analysis.');
        }

        $lock = Cache::lock("document-analysis:start:product:{$product->getKey()}", 10);

        if (! $lock->get()) {
            throw new LogicException('Document analysis could not be started right now. Please try again.');
        }

        try {
            $activeRun = $product->documentAnalysisRuns()->active()->latest('id')->first();

            if ($activeRun instanceof DocumentAnalysisRun) {
                throw new LogicException('Document analysis is already running for this product.');
            }

            $batchSize = $this->batchSize();
            $totalDocuments = $documents->count();
            $totalBatches = (int) ceil($totalDocuments / $batchSize);

            $run = DocumentAnalysisRun::query()->create([
                'distributor_id' => $product->distributor_id,
                'product_id' => $product->getKey(),
                'requested_by_user_id' => $requestedBy?->getKey(),
                'status' => DocumentAnalysisRun::STATUS_QUEUED,
                'current_phase' => 'Queued',
                'batch_size' => $batchSize,
                'total_documents' => $totalDocuments,
                'total_batches' => $totalBatches,
            ]);

            RunProductDocumentAnalysis::dispatch($run->getKey());

            return $run;
        } finally {
            $lock->release();
        }
    }

    public function process(DocumentAnalysisRun $run): void
    {
        $run->loadMissing(['product.documents.media', 'product.components', 'requestedBy']);

        $product = $run->product;

        if (! $product instanceof Product) {
            $run->markFailed('The product for this analysis run no longer exists.');

            return;
        }

        $documents = $this->analyzableDocuments($product);

        if ($documents->isEmpty()) {
            $run->markFailed('No analyzable document files were found for this product.');

            return;
        }

        $totalDocuments = $documents->count();
        $totalBatches = max(1, (int) ceil($totalDocuments / $run->batch_size));

        $run->markRunning($totalDocuments, $totalBatches);

        $documentResults = [];
        $allFindings = collect();
        $batchSummaries = [];

        foreach ($documents->chunk($run->batch_size) as $batchIndex => $batchDocuments) {
            $run->updateProgress(
                phase: 'Analysing documents…',
                completedBatches: $batchIndex,
                processedDocuments: $batchIndex * $run->batch_size,
            );

            $analysis = $this->analyzeBatch($batchDocuments->values());
            $batchResults = collect($analysis['documents'] ?? [])->keyBy('document_id');

            foreach ($batchDocuments as $document) {
                $result = $this->normalizeDocumentResult($document, $batchResults->get($document->getKey()));

                $documentResults[] = $result;
                $allFindings = $allFindings->merge($result['findings']);
            }

            $batchSummaries[] = $analysis['batch_summary'] ?? 'Batch completed.';

            $processedDocuments = min($totalDocuments, ($batchIndex + 1) * $run->batch_size);

            $run->updateProgress(
                phase: 'Analysing documents…',
                completedBatches: $batchIndex + 1,
                processedDocuments: $processedDocuments,
            );
        }

        $run->updateProgress(
            phase: 'Detecting components…',
            completedBatches: $totalBatches,
            processedDocuments: $totalDocuments,
        );

        $createdComponents = $this->componentDetector->detect($product->fresh(['documents.media', 'components']));
        $overallScore = (int) round(collect($documentResults)->avg('score') ?? 0);

        $run->markCompleted(
            result: [
                'overall_score' => $overallScore,
                'overall_rating' => $this->overallRating($overallScore),
                'document_count' => count($documentResults),
                'documents' => array_values($documentResults),
                'findings' => $allFindings->filter()->unique()->values()->take(12)->all(),
                'batch_summaries' => $batchSummaries,
            ],
            detectedComponents: $createdComponents,
        );

        if ($run->requestedBy instanceof User) {
            $run->requestedBy->notify(new ProductDocumentAnalysisCompleted($product, $run->fresh()));
        }
    }

    protected function batchSize(): int
    {
        return max(1, (int) config('document-analysis.batch_size', 6));
    }

    protected function provider(): string
    {
        return (string) config('document-analysis.ai.provider', config('ai.default', 'openai'));
    }

    protected function model(): ?string
    {
        $model = config('document-analysis.ai.model');

        return filled($model) ? (string) $model : null;
    }

    protected function assertAiConfiguration(): void
    {
        $provider = $this->provider();

        if (! in_array($provider, config('document-analysis.ai.supported_providers', []), true)) {
            throw new LogicException('The configured AI provider does not support document analysis attachments.');
        }

        if (blank(config("ai.providers.{$provider}.key"))) {
            throw new LogicException('Configure an AI provider key before running document analysis.');
        }
    }

    /**
     * @return Collection<int, Document>
     */
    protected function analyzableDocuments(Product $product): Collection
    {
        return $product->documents
            ->filter(fn (Document $document): bool => $document->getMedia(Document::FILE_COLLECTION)->isNotEmpty())
            ->values();
    }

    /**
     * @param  Collection<int, Document>  $documents
     * @return array<string, mixed>
     */
    protected function analyzeBatch(Collection $documents): array
    {
        $response = (new ProductDocumentBatchAgent)->prompt(
            $this->buildPrompt($documents),
            attachments: $this->buildAttachments($documents),
            provider: $this->provider(),
            model: $this->model(),
        );

        return is_array($response->structured ?? null)
            ? $response->structured
            : [];
    }

    /**
     * @param  Collection<int, Document>  $documents
     * @return array<int, StoredDocument>
     */
    protected function buildAttachments(Collection $documents): array
    {
        return $documents
            ->flatMap(fn (Document $document): Collection => $document->getMedia(Document::FILE_COLLECTION)
                ->map(fn ($media) => AiDocumentFile::fromStorage($media->getPathRelativeToRoot(), $media->disk)
                    ->as($media->file_name)))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Document>  $documents
     */
    protected function buildPrompt(Collection $documents): string
    {
        $documentList = $documents
            ->map(function (Document $document): string {
                $files = $document->getMedia(Document::FILE_COLLECTION)
                    ->pluck('file_name')
                    ->implode(', ');

                return implode(PHP_EOL, [
                    "Document ID: {$document->getKey()}",
                    'Type: '.($document->type?->label() ?? 'Other'),
                    'Files: '.$files,
                ]);
            })
            ->implode(PHP_EOL.PHP_EOL);

        return <<<'TEXT'
Review the attached compliance documents for a consumer product.

Return one result for every document listed below. Use the attachments and the metadata together. Do not skip any document_id.

Documents:
TEXT
            .PHP_EOL.PHP_EOL
            .$documentList;
    }

    /**
     * @param  array<string, mixed>|null  $result
     * @return array<string, mixed>
     */
    protected function normalizeDocumentResult(Document $document, ?array $result): array
    {
        $score = min(100, max(0, (int) data_get($result, 'score', 0)));
        $rating = (string) data_get($result, 'rating', $this->documentRating($score));
        $summary = (string) data_get($result, 'summary', 'No summary returned for this document.');
        $findings = collect(data_get($result, 'findings', []))
            ->filter(fn (mixed $finding): bool => filled($finding))
            ->map(fn (mixed $finding): string => (string) $finding)
            ->values()
            ->all();

        return [
            'document_id' => $document->getKey(),
            'type' => $document->type?->label() ?? 'Other',
            'score' => $score,
            'rating' => $rating,
            'summary' => $summary,
            'findings' => $findings,
            'files' => $document->getMedia(Document::FILE_COLLECTION)->pluck('file_name')->values()->all(),
            'component' => $document->productComponent?->name,
        ];
    }

    protected function overallRating(int $score): string
    {
        return match (true) {
            $score >= 85 => 'compliant',
            $score >= 60 => 'warning',
            default => 'non_compliant',
        };
    }

    protected function documentRating(int $score): string
    {
        return match (true) {
            $score >= 85 => 'compliant',
            $score >= 60 => 'warning',
            $score > 0 => 'non_compliant',
            default => 'inconclusive',
        };
    }
}
