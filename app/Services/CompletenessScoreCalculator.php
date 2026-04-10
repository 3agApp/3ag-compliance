<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\Product;

class CompletenessScoreCalculator
{
    private const array SAFETY_FIELDS = ['safety_text', 'warning_text', 'age_grading'];

    /**
     * Calculate and persist the completeness score for a product.
     */
    public function calculate(Product $product): float
    {
        $product->loadMissing(['template', 'currentDocuments', 'safetyEntry']);

        $requiredTypes = $this->getRequiredDocumentTypes($product);

        if ($requiredTypes === []) {
            $score = 100.0;
            $product->updateQuietly(['completeness_score' => $score]);

            return $score;
        }

        $fulfilledTypes = $product->currentDocuments
            ->pluck('type')
            ->map(fn (DocumentType $type): string => $type->value)
            ->unique()
            ->all();

        $documentScore = $this->calculateDocumentScore($requiredTypes, $fulfilledTypes);
        $safetyScore = $this->calculateSafetyScore($product, $documentScore['totalWeight']);

        $earnedPoints = $documentScore['earnedPoints'] + $safetyScore['earnedPoints'];
        $totalPoints = $documentScore['totalWeight'] + $safetyScore['totalWeight'];

        $score = $totalPoints > 0
            ? round(($earnedPoints / $totalPoints) * 100, 2)
            : 100.0;

        $product->updateQuietly(['completeness_score' => $score]);

        return $score;
    }

    /**
     * @return list<string>
     */
    private function getRequiredDocumentTypes(Product $product): array
    {
        return $product->template?->required_document_types ?? [];
    }

    /**
     * @param  list<string>  $requiredTypes
     * @param  list<string>  $fulfilledTypes
     * @return array{earnedPoints: int, totalWeight: int}
     */
    private function calculateDocumentScore(array $requiredTypes, array $fulfilledTypes): array
    {
        $totalWeight = 0;
        $earnedPoints = 0;

        foreach ($requiredTypes as $typeValue) {
            $type = DocumentType::tryFrom($typeValue);

            if ($type === null) {
                continue;
            }

            $weight = $type->complianceWeight();
            $totalWeight += $weight;

            if (in_array($typeValue, $fulfilledTypes, true)) {
                $earnedPoints += $weight;
            }
        }

        return [
            'earnedPoints' => $earnedPoints,
            'totalWeight' => $totalWeight,
        ];
    }

    /**
     * @return array{earnedPoints: float, totalWeight: float}
     */
    private function calculateSafetyScore(Product $product, int $documentTotalWeight): array
    {
        $safetyWeight = max(3, (int) round($documentTotalWeight * 0.25));

        $filledCount = 0;
        $safetyEntry = $product->safetyEntry;

        if ($safetyEntry !== null) {
            foreach (self::SAFETY_FIELDS as $field) {
                if (filled($safetyEntry->{$field})) {
                    $filledCount++;
                }
            }
        }

        $earnedPoints = ($filledCount / count(self::SAFETY_FIELDS)) * $safetyWeight;

        return [
            'earnedPoints' => $earnedPoints,
            'totalWeight' => (float) $safetyWeight,
        ];
    }
}
