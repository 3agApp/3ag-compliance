<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PublicProductController extends Controller
{
    public function show(Product $product): Response
    {
        $product->load(['supplier:id,name', 'brand:id,name', 'category:id,name', 'media', 'safetyEntry']);

        return Inertia::render('products/public/show', [
            'product' => [
                'name' => $product->name,
                'public_uuid' => $product->public_uuid,
                'ean' => $product->ean,
                'internal_article_number' => $product->internal_article_number,
                'supplier_article_number' => $product->supplier_article_number,
                'order_number' => $product->order_number,
                'seal_status' => $product->sealStatus()->value,
                'supplier' => $product->supplier ? ['name' => $product->supplier->name] : null,
                'brand' => $product->brand ? ['name' => $product->brand->name] : null,
                'category' => $product->category ? ['name' => $product->category->name] : null,
                'images' => $product->getMedia('images')->map(fn (Media $media): array => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'preview_url' => $media->getUrl('preview'),
                    'name' => $media->file_name,
                ])->values()->all(),
                'documents' => $this->publicDocuments($product),
                'safety_entry' => $product->safetyEntry,
                'created_at' => $product->created_at?->toIso8601String(),
                'updated_at' => $product->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function publicDocuments(Product $product): array
    {
        return $product->currentDocuments()
            ->where('public_download', true)
            ->with('media')
            ->orderBy('type')
            ->get()
            ->map(function (Document $document): array {
                /** @var Media|null $media */
                $media = $document->getFirstMedia('file');

                return [
                    'id' => $document->id,
                    'type' => $document->type->value,
                    'type_label' => $document->type->label(),
                    'file_name' => $media?->file_name,
                    'file_url' => $media?->getUrl(),
                    'file_size' => $media?->size,
                    'mime_type' => $media?->mime_type,
                ];
            })
            ->values()
            ->all();
    }
}
