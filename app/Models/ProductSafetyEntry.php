<?php

namespace App\Models;

use Database\Factories\ProductSafetyEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'safety_text', 'warning_text', 'age_grading', 'material_information', 'usage_restrictions', 'safety_instructions', 'additional_notes'])]
class ProductSafetyEntry extends Model
{
    /** @use HasFactory<ProductSafetyEntryFactory> */
    use HasFactory;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
