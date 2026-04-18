<?php

namespace App\Models;

use Database\Factories\DocumentAnalysisRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

#[Fillable([
    'distributor_id',
    'product_id',
    'requested_by_user_id',
    'status',
    'current_phase',
    'batch_size',
    'total_documents',
    'processed_documents',
    'total_batches',
    'completed_batches',
    'result',
    'detected_components',
    'failure_message',
    'started_at',
    'finished_at',
    'failed_at',
])]
class DocumentAnalysisRun extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /** @use HasFactory<DocumentAnalysisRunFactory> */
    use HasFactory;

    /**
     * @var array<string, string|int>
     */
    protected $attributes = [
        'status' => self::STATUS_QUEUED,
        'current_phase' => 'Queued',
        'batch_size' => 6,
        'total_documents' => 0,
        'processed_documents' => 0,
        'total_batches' => 0,
        'completed_batches' => 0,
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_QUEUED, self::STATUS_RUNNING]);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_RUNNING], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function progressPercentage(): int
    {
        if ($this->total_batches > 0) {
            return (int) round(($this->completed_batches / $this->total_batches) * 100);
        }

        if ($this->total_documents > 0) {
            return (int) round(($this->processed_documents / $this->total_documents) * 100);
        }

        return 0;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function documentResults(): Collection
    {
        return collect($this->result['documents'] ?? []);
    }

    public function markRunning(int $totalDocuments, int $totalBatches): void
    {
        $this->forceFill([
            'status' => self::STATUS_RUNNING,
            'current_phase' => 'Preparing documents…',
            'total_documents' => $totalDocuments,
            'total_batches' => $totalBatches,
            'started_at' => $this->started_at ?? now(),
            'failure_message' => null,
            'failed_at' => null,
        ])->save();
    }

    public function updateProgress(string $phase, int $completedBatches, int $processedDocuments): void
    {
        $this->forceFill([
            'status' => self::STATUS_RUNNING,
            'current_phase' => $phase,
            'completed_batches' => $completedBatches,
            'processed_documents' => $processedDocuments,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<int, string>  $detectedComponents
     */
    public function markCompleted(array $result, array $detectedComponents): void
    {
        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'current_phase' => 'Completed',
            'completed_batches' => $this->total_batches,
            'processed_documents' => $this->total_documents,
            'result' => $result,
            'detected_components' => array_values(array_unique($detectedComponents)),
            'finished_at' => now(),
            'failed_at' => null,
            'failure_message' => null,
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'current_phase' => 'Failed',
            'failure_message' => $message,
            'failed_at' => now(),
        ])->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'distributor_id' => 'integer',
            'product_id' => 'integer',
            'requested_by_user_id' => 'integer',
            'batch_size' => 'integer',
            'total_documents' => 'integer',
            'processed_documents' => 'integer',
            'total_batches' => 'integer',
            'completed_batches' => 'integer',
            'result' => 'array',
            'detected_components' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
