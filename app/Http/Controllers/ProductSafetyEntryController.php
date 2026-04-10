<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CompletenessScoreCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSafetyEntryController extends Controller
{
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'safety_text' => ['nullable', 'string', 'max:5000'],
            'warning_text' => ['nullable', 'string', 'max:5000'],
            'age_grading' => ['nullable', 'string', 'max:5000'],
            'material_information' => ['nullable', 'string', 'max:5000'],
            'usage_restrictions' => ['nullable', 'string', 'max:5000'],
            'safety_instructions' => ['nullable', 'string', 'max:5000'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $safetyEntry = $product->safetyEntry()->updateOrCreate(
            ['product_id' => $product->id],
            $validated,
        );

        $score = app(CompletenessScoreCalculator::class)->calculate($product);

        return response()->json([
            'safety_entry' => $safetyEntry,
            'completeness_score' => $score,
        ]);
    }
}
