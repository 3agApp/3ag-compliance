<?php

namespace App\Models;

use Database\Factories\ProductComponentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['distributor_id', 'product_id', 'name', 'code', 'detected_at'])]
class ProductComponent extends Model
{
    /** @use HasFactory<ProductComponentFactory> */
    use HasFactory;

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'distributor_id' => 'integer',
            'product_id' => 'integer',
            'detected_at' => 'datetime',
        ];
    }
}
