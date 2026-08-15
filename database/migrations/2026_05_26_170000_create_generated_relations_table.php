<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_entity_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('target');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_relations');
    }
};
