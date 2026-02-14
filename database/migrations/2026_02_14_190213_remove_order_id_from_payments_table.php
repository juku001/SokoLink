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
        Schema::table('payments', function (Blueprint $table) {
            // Drop foreign key if it exists
            $table->dropForeign(['order_id']);

            // Drop the column
            $table->dropColumn('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Recreate column
            $table->unsignedBigInteger('order_id')->nullable()->after('id');

            // Recreate foreign key (adjust table/column as needed)
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }
};
