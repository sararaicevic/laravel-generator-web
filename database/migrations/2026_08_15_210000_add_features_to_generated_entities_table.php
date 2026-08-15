<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_entities', function (Blueprint $table) {
            $table->boolean('has_index')->default(true);
            $table->boolean('has_create')->default(true);
            $table->boolean('has_edit')->default(true);
            $table->boolean('has_show')->default(true);
            $table->boolean('allows_delete')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('generated_entities', function (Blueprint $table) {
            $table->dropColumn([
                'has_index',
                'has_create',
                'has_edit',
                'has_show',
                'allows_delete',
            ]);
        });
    }
};
