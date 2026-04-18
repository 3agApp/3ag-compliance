<?php

use App\Models\Distributor;
use App\Models\Product;
use App\Models\User;
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
        Schema::create('document_analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Distributor::class)->constrained();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('queued');
            $table->string('current_phase')->nullable();
            $table->unsignedInteger('batch_size')->default(6);
            $table->unsignedInteger('total_documents')->default(0);
            $table->unsignedInteger('processed_documents')->default(0);
            $table->unsignedInteger('total_batches')->default(0);
            $table->unsignedInteger('completed_batches')->default(0);
            $table->json('result')->nullable();
            $table->json('detected_components')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['distributor_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_analysis_runs');
    }
};
