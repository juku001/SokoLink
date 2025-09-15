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
        Schema::create('escrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('seller_amount', 12, 2);
            $table->decimal('platform_fee', 12, 2);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['holding', 'released', 'refunded'])->default('holding');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escrows');
    }
};
