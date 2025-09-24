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
        Schema::create('airtel_callback_logs', function (Blueprint $table) {
            $table->id();
            $table->json('payload');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->nullable()->unique();
            $table->string('airtel_money_id')->nullable()->unique();
            $table->string('amount')->nullable();
            $table->string('message')->nullable();
            $table->string('status_code')->nullable();
            $table->string('result')->nullable();
            $table->enum('status', ['failed', 'success'])->default('failed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airtel_callback_logs');
    }
};
