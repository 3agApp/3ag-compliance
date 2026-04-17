<?php

use App\Models\Supplier;
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
        Schema::table('distributor_user', function (Blueprint $table) {
            $table->foreignIdFor(Supplier::class)
                ->nullable()
                ->after('distributor_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributor_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
