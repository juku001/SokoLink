<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation
            $table->morphs('reviewable'); // creates reviewable_id and reviewable_type

            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who wrote the review

            $table->tinyInteger('rating')->default(5); // rating, e.g., 1-5
            $table->text('review')->nullable();    // review text

            $table->boolean('is_verified_purchase')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
