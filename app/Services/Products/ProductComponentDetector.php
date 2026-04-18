<?php

namespace App\Services\Products;

use App\Models\Document;
use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Support\Str;

class ProductComponentDetector
{
    /**
     * @return array<int, string>
     */
    public function detect(Product $product): array
    {
        $product->loadMissing('documents.media', 'components');

        $createdComponents = [];

        foreach ($product->documents as $document) {
            $match = $this->matchDocument($document);

            if ($match === null) {
                continue;
            }

            $component = ProductComponent::query()->firstOrCreate(
                [
                    'product_id' => $product->getKey(),
                    'name' => $match['name'],
                ],
                [
                    'distributor_id' => $product->distributor_id,
                    'code' => $match['code'],
                    'detected_at' => now(),
                ],
            );

            if ($component->wasRecentlyCreated) {
                $createdComponents[] = $component->name;
            } elseif (blank($component->code) && filled($match['code'])) {
                $component->forceFill(['code' => $match['code']])->saveQuietly();
            }

            if ((int) $document->product_component_id !== (int) $component->getKey()) {
                $document->forceFill([
                    'product_component_id' => $component->getKey(),
                ])->saveQuietly();
            }
        }

        return array_values(array_unique($createdComponents));
    }

    /**
     * @return array{code: string, name: string}|null
     */
    protected function matchDocument(Document $document): ?array
    {
        $haystack = Str::of(implode(' ', [
            $document->type?->value,
            $document->type?->label(),
            $document->getMedia(Document::FILE_COLLECTION)->pluck('file_name')->implode(' '),
        ]))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->value();

        foreach (config('document-analysis.component_detection.rules', []) as $rule) {
            foreach ($rule['patterns'] ?? [] as $pattern) {
                if (preg_match($pattern, $haystack) === 1) {
                    return [
                        'code' => (string) $rule['code'],
                        'name' => (string) $rule['name'],
                    ];
                }
            }
        }

        return null;
    }
}
