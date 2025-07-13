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
            $table->string('transaction_id')->nullable()->after('status');
            $table->string('payment_type')->nullable()->after('transaction_id');
            $table->decimal('gross_amount', 16, 2)->nullable()->after('payment_type');
            $table->string('transaction_status')->nullable()->after('gross_amount');
            $table->string('fraud_status')->nullable()->after('transaction_status');
            $table->json('payment_data')->nullable()->after('fraud_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_id',
                'payment_type',
                'gross_amount',
                'transaction_status',
                'fraud_status',
                'payment_data'
            ]);
        });
    }
};
