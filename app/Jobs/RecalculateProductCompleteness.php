<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Template;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateProductCompleteness implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function __construct(
        public int $templateId,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->templateId;
    }

    public function handle(): void
    {
        $template = Template::find($this->templateId);

        if (! $template) {
            return;
        }

        Product::query()
            ->where('template_id', $template->getKey())
            ->with([
                'template:id,required_document_types,required_data_fields',
                'safetyEntry:id,product_id,safety_text,warning_text,age_grading,material_information,usage_restrictions,safety_instructions,additional_notes',
                'documents:id,product_id,type',
            ])
            ->cursor()
            ->each(function (Product $product): void {
                $product->refreshCompletenessScore();
            });
    }
}
