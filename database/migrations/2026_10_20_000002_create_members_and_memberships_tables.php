<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ئەندامان و یاریزانان (Members)
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->string('member_code')->index(); // ناسنامەی یاریزان (بێهاوتا لەگەڵ gym_id)
            $table->string('full_name');
            $table->string('phone')->index();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('national_id')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('photo_url')->nullable();
            $table->text('health_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('allow_credit')->default(false);
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->decimal('current_debt', 12, 2)->default(0.00);
            $table->enum('status', ['active', 'expired', 'frozen', 'cancelled', 'pending_payment'])->default('active');
            $table->foreignId('assigned_trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['gym_id', 'member_code']);
        });

        // 2. پلانەکانی بەشداری (Membership Plans)
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('duration_days'); // ژمارەی ڕۆژەکانی بەشداری
            $table->integer('visits_limit')->nullable(); // ئەگەر بەپێی ژمارەی سەردان بێت
            $table->time('allowed_entry_start')->nullable(); // بۆ نموونە: 08:00
            $table->time('allowed_entry_end')->nullable();   // بۆ نموونە: 16:00
            $table->json('allowed_days_of_week')->nullable(); // ڕۆژە ڕێگەپێدراوەکان [0,1,2,3,4,5,6]
            $table->decimal('renewal_discount_percentage', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. بەشدارییە کردارییەکانی ئەندام (Memberships / Subscriptions)
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained('membership_plans')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('original_end_date'); // پاراستنی بەرواری بنەڕەتی پێش ڕاگرتن
            $table->integer('visits_limit')->nullable();
            $table->integer('visits_used')->default(0);
            $table->decimal('original_price', 12, 2);
            $table->decimal('paid_price', 12, 2);
            $table->enum('status', ['active', 'expired', 'frozen', 'cancelled', 'pending_payment'])->default('active');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();

            $table->index(['gym_id', 'member_id', 'status']);
        });

        // 4. ڕاگرتنی بەشداری (Membership Freeze)
        Schema::create('membership_freezes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('membership_id')->constrained('memberships')->cascadeOnDelete();
            $table->date('freeze_start_date');
            $table->date('freeze_end_date');
            $table->integer('days_count'); // ژمارەی ئەو ڕۆژانەی بەشدارییەکەی بۆ درێژ دەکرێتەوە
            $table->string('reason');
            $table->foreignId('approved_by_user_id')->constrained('users');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
        });

        // 5. ئۆفەر و داشکاندنەکان (Promotions)
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('title');
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 12, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('usage_limit')->nullable();
            $table->integer('times_used')->default(0);
            $table->decimal('min_purchase_amount', 12, 2)->default(0.00);
            $table->boolean('is_for_new_members_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promotion_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->decimal('discount_applied', 12, 2);
            $table->timestamp('redeemed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_redemptions');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('membership_freezes');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_plans');
        Schema::dropIfExists('members');
    }
};
