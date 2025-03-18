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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');  // income, expense, transfer, etc.
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('categories')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->date('transaction_date');
            $table->string('status')->default('completed'); // pending, completed, cancelled
            $table->string('payment_method')->nullable(); // cash, credit card, bank transfer, etc.
            $table->string('reference_number')->nullable(); // For check numbers, transaction IDs, etc.
            $table->timestamps();
            $table->softDeletes(); // Add soft delete capability
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
