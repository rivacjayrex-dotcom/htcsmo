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
            $table->string('size', 100)->default('');
            $table->string('supplier', 255)->default('');
            $table->dropUnique(['item_name']);
            $table->unique(['item_name', 'size', 'supplier'], 'supplies_variant_unique');
        });

        Schema::table('requisition_items', function (Blueprint $table): void {
            $table->string('size', 100)->default('');
            $table->string('supplier', 255)->default('');
        });
    }

    public function down(): void
    {
        if (DB::table('supplies')->select('item_name')->groupBy('item_name')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot remove supply variants while multiple records share an item name.');
        }

        Schema::table('requisition_items', function (Blueprint $table): void {
            $table->dropColumn(['size', 'supplier']);
        });
        Schema::table('supplies', function (Blueprint $table): void {
            $table->dropUnique('supplies_variant_unique');
            $table->unique('item_name');
            $table->dropColumn(['size', 'supplier']);
        });
    }
};
