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
        Schema::table('bookings', function (Blueprint $table) {
            $table->date('return_date')->nullable()->after('booking_date');
            $table->decimal('total_amount', 10, 2)->default(0)->after('return_date');
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('remaining_balance', 10, 2)->default(0)->after('deposit_amount');

            // Drop old columns
            $table->dropColumn(['attire_name', 'payment_amount', 'balance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('attire_name')->after('invoice_number');
            $table->decimal('payment_amount', 10, 2)->default(0)->after('payment_status');
            $table->decimal('balance', 10, 2)->default(0)->after('payment_amount');

            $table->dropColumn(['return_date', 'total_amount', 'deposit_amount', 'remaining_balance']);
        });
    }
};
