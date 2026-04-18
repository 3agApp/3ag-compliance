<?php

use App\Models\Distributor;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Distributor::class)->constrained();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'name']);
            $table->unique(['product_id', 'code']);
            $table->index(['distributor_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_components');
    }
};
