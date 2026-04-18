<?php

namespace App\Jobs;

use App\Models\DocumentAnalysisRun;
use App\Services\Documents\ProductDocumentAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunProductDocumentAnalysis implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public int $documentAnalysisRunId,
    ) {}

    public function handle(ProductDocumentAnalysisService $analysisService): void
    {
        $run = DocumentAnalysisRun::query()->find($this->documentAnalysisRunId);

        if (! $run instanceof DocumentAnalysisRun) {
            return;
        }

        $analysisService->process($run);
    }

    public function failed(?Throwable $exception): void
    {
        $run = DocumentAnalysisRun::query()->find($this->documentAnalysisRunId);

        if ($run instanceof DocumentAnalysisRun && ! $run->isCompleted()) {
            $run->markFailed($exception?->getMessage() ?? 'Document analysis failed.');
        }
    }
}
