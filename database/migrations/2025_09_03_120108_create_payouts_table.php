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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->string('txn_id')->nullable()->unique();

            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();

            $table->string('payout_account');
            $table->string('currency')->default('TZS');
            $table->decimal('amount', 10, 2)->default(0);

            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();

            $table->string('acknowledgement')->nullable();
            $table->string('response')->nullable();
            $table->json('data')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
