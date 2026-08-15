<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('generated_projects', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('generated_projects', function (Blueprint $table) {
            if (Schema::hasColumn('generated_projects', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};

