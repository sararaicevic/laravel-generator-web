<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_entities', function (Blueprint $table) {
            if (!Schema::hasColumn('generated_entities', 'display_field')) {
                $table->string('display_field')->nullable()->after('allows_delete');
            }
        });
    }

    public function down(): void
    {
        Schema::table('generated_entities', function (Blueprint $table) {
            if (Schema::hasColumn('generated_entities', 'display_field')) {
                $table->dropColumn('display_field');
            }
        });
    }
};
