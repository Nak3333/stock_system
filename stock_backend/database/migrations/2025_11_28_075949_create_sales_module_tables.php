<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('cashier_id')->constrained('users');
            $table->timestampTz('sale_datetime')->useCurrent();
            $table->decimal('total_amount', 12, 2)->default(0);     // gross
            $table->decimal('total_discount', 12, 2)->default(0);
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);       // payable
            $table->string('payment_status', 20)->default('PAID');  // PAID/PARTIAL/UNPAID
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('sale_id')->constrained('sales');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_amt', 10, 2)->default(0);
            $table->decimal('line_total', 12, 2);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('sale_id')->constrained('sales');
            $table->string('payment_method', 30);       // CASH/CARD/MOBILE/etc.
            $table->decimal('amount', 12, 2);
            $table->timestampTz('paid_at')->useCurrent();
            $table->string('reference_no', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
