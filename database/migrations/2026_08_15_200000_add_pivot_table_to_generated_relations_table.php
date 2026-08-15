<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_relations', function (Blueprint $table) {
            $table->string('pivot_table')->nullable()->after('target');
        });
    }

    public function down(): void
    {
        Schema::table('generated_relations', function (Blueprint $table) {
            $table->dropColumn('pivot_table');
        });
    }
};
