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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('store_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('sale_ref')->unique();

            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('payment_type',['cash', 'mno', 'bank', 'card'])->default('mno');
            
            $table->string('buyer_name')->nullable();
            $table->decimal('amount', 10, 2);

            $table->date('sales_date');
            $table->time('sales_time');
            
            $table->enum('status', ['pending', 'completed'])->default('completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
