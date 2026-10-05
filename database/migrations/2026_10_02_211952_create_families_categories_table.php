<?php

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
        Schema::create('families_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('families_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('categories_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['families_id', 'categories_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('families_categories');
    }
};
