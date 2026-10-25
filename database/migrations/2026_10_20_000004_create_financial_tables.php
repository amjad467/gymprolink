<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. حساب و سندوقەکانی پارەی هۆڵ (Cash Accounts / Safes)
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->string('name'); // بۆ نموونە: سندوقی سەرەکی، سندوقی ڕیسێپشن
            $table->string('account_type')->default('cash_drawer'); // cash_drawer, safe, bank_account
            $table->decimal('current_balance', 14, 2)->default(0.00);
            $table->string('currency', 10)->default('IQD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. فاکتۆرەکانی بەشداری و خزمەتگوزارییەکانی هۆڵ (Invoices)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->string('discount_reason')->nullable();
            $table->foreignId('discount_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('remaining_balance', 12, 2)->default(0.00);
            $table->enum('status', ['paid', 'partially_paid', 'due', 'refunded', 'cancelled'])->default('due');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['gym_id', 'status', 'created_at']);
        });

        // 3. پارەدانەکان (Payments)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('cash_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'debt_settlement'])->default('cash');
            $table->string('reference_number')->nullable(); // ژمارەی وەصڵ یان پسووڵەی کارت
            $table->foreignId('received_by_user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. خەرجییەکانی هۆڵ (Gym Expenses)
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('cash_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete();
            $table->string('category'); // rent, electricity, maintenance, salaries, cleaning, equipment, other
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('spent_at');
            $table->string('paid_to')->nullable();
            $table->string('receipt_url')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['gym_id', 'category', 'spent_at']);
        });

        // 5. تۆماری وردی هەموو گۆڕانکاری و کردارە هەستیارەکان (Audit Logs)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->nullable()->constrained('gyms')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // e.g. member.create, discount.override, membership.freeze, payment.refund
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['gym_id', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('cash_accounts');
    }
};
