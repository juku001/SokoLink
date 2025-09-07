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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['mno', 'bank', 'card'])->default('mno');
            $table->string('display');
            $table->string('image')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
        Artisan::call('db:seed', ['--class' => 'PaymentMethodSeeder']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
