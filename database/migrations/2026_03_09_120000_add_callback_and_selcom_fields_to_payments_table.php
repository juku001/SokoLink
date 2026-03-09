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
            $table->text('callback_data')->nullable()->after('notes');
            $table->text('gateway_data')->nullable()->after('notes');
            $table->string('selcom_order_id')->nullable()->after('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('callback_data');
            $table->dropColumn('gateway_data');
            $table->dropColumn('selcom_order_id');
        });
    }
};
