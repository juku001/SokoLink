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
        Schema::create('notification_histories', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->string('txn_id')->nullable();
            $table->string('pay_account');
            $table->foreignId('pay_method')->constrained('payment_methods')->onDelete('cascade');
            $table->decimal('amount', 10,2)->default(0);
            $table->timestamp('payment_date')->nullable();
            $table->enum('status',['pending', 'success', 'failed'])->default('pending');
            $table->text('notes')->nullable();
                        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_histories');
    }
};
