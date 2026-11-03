<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplies', function (Blueprint $table): void {
            $table->string('variant_key', 64)->default('');
            $table->dropUnique('supplies_variant_unique');
            $table->unique(['item_name', 'size', 'supplier', 'variant_key'], 'supplies_variant_unique');
        });
        Schema::table('requisition_items', function (Blueprint $table): void {
            $table->json('variant_attributes')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('supplies')->select('item_name', 'size', 'supplier')
            ->groupBy('item_name', 'size', 'supplier')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot remove priced combinations while stock records share an item, size and supplier.');
        }
        Schema::table('requisition_items', fn (Blueprint $table) => $table->dropColumn('variant_attributes'));
        Schema::table('supplies', function (Blueprint $table): void {
            $table->dropUnique('supplies_variant_unique');
            $table->dropColumn('variant_key');
            $table->unique(['item_name', 'size', 'supplier'], 'supplies_variant_unique');
        });
    }
};
