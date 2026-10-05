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
        Schema::create('families_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('families_id')->constrained('families')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('full_link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('families_share_links');
    }
};
