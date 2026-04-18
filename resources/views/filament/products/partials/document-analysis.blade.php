@php
    /** @var \App\Models\Product|null $record */
    $run = $record?->documentAnalysisRuns()->latest('id')->first();
    $result = $run?->result ?? [];
    $documents = collect($result['documents'] ?? []);
    $overallRating = str((string) ($result['overall_rating'] ?? ''))->replace('_', ' ')->headline();
    $currentBatch = $run && $run->isActive()
        ? min(max(1, $run->completed_batches + 1), max(1, $run->total_batches))
        : null;
@endphp

<div class="space-y-4">
    @if (! $run)
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-5 text-sm text-gray-600">
            Run document analysis to score all attached files, surface findings, and detect likely product components.
        </div>
    @elseif ($run->isActive())
        <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-4 text-sm text-sky-900">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="font-semibold">{{ $run->current_phase }}</p>
                    <p class="text-sky-800">
                        Batch {{ $currentBatch }} of {{ max(1, $run->total_batches) }}
                    </p>
                </div>
                <div class="text-sky-800">
                    {{ $run->processed_documents }} / {{ $run->total_documents }} documents
                </div>
            </div>

            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-sky-100">
                <div
                    class="h-full rounded-full bg-sky-500 transition-all"
                    style="width: {{ min(100, max(0, $run->progressPercentage())) }}%;"
                ></div>
            </div>
        </div>
    @elseif ($run->status === \App\Models\DocumentAnalysisRun::STATUS_FAILED)
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-900">
            <p class="font-semibold">Document analysis failed</p>
            <p>{{ $run->failure_message ?: 'The analysis run ended without a detailed error message.' }}</p>
        </div>
    @else
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-950">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="font-semibold">Analysis complete</p>
                    <p>
                        Overall rating: {{ $overallRating }}
                        @if (array_key_exists('overall_score', $result))
                            ({{ $result['overall_score'] }}/100)
                        @endif
                    </p>
                </div>
                <div>
                    {{ $result['document_count'] ?? 0 }} documents analysed
                </div>
            </div>

            @if (filled($run->detected_components))
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($run->detected_components as $component)
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-900">
                            {{ $component }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        @if (filled($result['findings'] ?? []))
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-4">
                <h4 class="text-sm font-semibold text-gray-900">Top findings</h4>
                <ul class="mt-3 space-y-2 text-sm text-gray-700">
                    @foreach ($result['findings'] as $finding)
                        <li>{{ $finding }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($documents->isNotEmpty())
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-900">
                    Per-document assessment
                </div>

                <div class="divide-y divide-gray-200">
                    @foreach ($documents as $document)
                        <div class="px-4 py-4 text-sm">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ implode(', ', $document['files'] ?? ['Untitled document']) }}
                                    </p>
                                    <p class="text-gray-500">{{ $document['type'] ?? 'Other' }}</p>
                                </div>
                                <div class="text-gray-900">
                                    {{ $document['score'] ?? 0 }}/100
                                    <span class="text-gray-500">{{ str((string) ($document['rating'] ?? ''))->replace('_', ' ')->headline() }}</span>
                                </div>
                            </div>

                            <p class="mt-2 text-gray-700">{{ $document['summary'] ?? 'No summary provided.' }}</p>

                            @if (filled($document['findings'] ?? []))
                                <ul class="mt-2 space-y-1 text-gray-600">
                                    @foreach ($document['findings'] as $finding)
                                        <li>{{ $finding }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>