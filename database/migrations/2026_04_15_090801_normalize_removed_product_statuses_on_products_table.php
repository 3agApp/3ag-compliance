<?php

use App\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        DB::table('products')
            ->where('status', 'submitted')
            ->update(['status' => ProductStatus::UnderReview->value]);

        DB::table('products')
            ->where('status', 'completed')
            ->update(['status' => ProductStatus::Approved->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration merges removed statuses into supported ones.
        // Rolling it back cannot safely distinguish migrated rows from legitimate values.
    }
};
