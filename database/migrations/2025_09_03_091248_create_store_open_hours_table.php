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
        Schema::create('store_open_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('day_of_week');          // e.g. 'monday', 'tuesday'
            $table->time('opens_at');               // e.g. 09:00:00
            $table->time('closes_at');              // e.g. 18:00:00
            $table->boolean('is_closed')->default(false); // for days fully closed
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_open_hours');
    }
};
