<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\DocumentAnalysisRun;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAnalysisRun>
 */
class DocumentAnalysisRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'distributor_id' => Distributor::factory(),
            'product_id' => fn (array $attributes): int => Product::factory()->create([
                'distributor_id' => $attributes['distributor_id'],
            ])->id,
            'requested_by_user_id' => User::factory(),
            'status' => DocumentAnalysisRun::STATUS_QUEUED,
            'current_phase' => 'Queued',
            'batch_size' => 6,
            'total_documents' => 0,
            'processed_documents' => 0,
            'total_batches' => 0,
            'completed_batches' => 0,
            'result' => null,
            'detected_components' => [],
            'failure_message' => null,
            'started_at' => null,
            'finished_at' => null,
            'failed_at' => null,
        ];
    }
}
