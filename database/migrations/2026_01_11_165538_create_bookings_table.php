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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('attire_name');
            $table->string('phone_number');
            $table->text('notes')->nullable();
            $table->text('accessories')->nullable();
            $table->string('payment_status')->default('pending');
            $table->decimal('payment_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->date('booking_date');
            $table->foreignId('time_slot_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
