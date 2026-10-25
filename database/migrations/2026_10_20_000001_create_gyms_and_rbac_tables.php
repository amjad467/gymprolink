<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. هۆڵەکان (Gyms / Tenants)
        Schema::create('gyms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('logo_url')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('opening_hours')->nullable();
            $table->decimal('default_credit_limit', 12, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // زیادکردنی ستوونەکان بۆ بەکارهێنەران
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_super_admin')->default(false)->after('password');
            $table->boolean('is_active')->default(true)->after('is_super_admin');
            $table->foreignId('current_gym_id')->nullable()->constrained('gyms')->nullOnDelete()->after('is_active');
        });

        // 2. ڕۆڵەکان (Roles)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. super_admin, gym_owner, gym_manager, receptionist, accountant, trainer, access_operator, shop_manager, shop_cashier, inventory_manager
            $table->string('display_name_ku');
            $table->string('display_name_ar');
            $table->string('display_name_en');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 3. مۆڵەتەکان (Permissions)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. members.view, members.create, access.devices.manage
            $table->string('group_name'); // e.g. members, access, finance, shop, settings
            $table->string('display_name_ku');
            $table->string('display_name_ar');
            $table->string('display_name_en');
            $table->timestamps();
        });

        // 4. مۆڵەتەکانی هەر ڕۆڵێک (Permission-Role Pivot)
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        // 5. پەیوەندی بەکارهێنەر، هۆڵ، و ڕۆڵ لەو هۆڵەدا (Gym-User-Role Pivot)
        Schema::create('gym_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['gym_id', 'user_id', 'role_id']);
        });

        // 6. ڕۆڵی گشتی بەکارهێنەران لە دەرەوەی هۆڵێکی دیاریکراو (وەک Super Admin)
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('gym_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_gym_id']);
            $table->dropColumn(['phone', 'is_super_admin', 'is_active', 'current_gym_id']);
        });

        Schema::dropIfExists('gyms');
    }
};
