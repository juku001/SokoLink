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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('category_id')->nullable()->constrained();
            $table->string('description')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('contact_mobile')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('shipping_origin')->nullable();
            $table->decimal('rating_avg')->default(0);
            $table->integer('rating_count')->default(0);
            $table->foreignId('region_id')->nullable()->constrained();
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
