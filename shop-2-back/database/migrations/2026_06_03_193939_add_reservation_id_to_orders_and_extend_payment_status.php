<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the payment_status enum to include 'cancelled'
        DB::statement(
            "ALTER TABLE orders MODIFY COLUMN payment_status
             ENUM('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending'"
        );

        Schema::table('orders', function (Blueprint $table) {
            $table->string('reservation_id')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('reservation_id');
        });

        DB::statement(
            "ALTER TABLE orders MODIFY COLUMN payment_status
             ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending'"
        );
    }
};
