<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->foreignId('access_device_id')->nullable()->constrained('access_devices')->nullOnDelete();
            $table->dateTime('check_in_at');
            $table->dateTime('check_out_at')->nullable();
            $table->integer('duration_minutes')->nullable(); // ماوەی مانەوە بە خولەک
            $table->enum('status', ['INSIDE', 'CHECKED_OUT'])->default('INSIDE');
            $table->string('check_in_method')->default('card_scan'); // card_scan, qr_code, manual, barcode
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['gym_id', 'status']);
            $table->index(['gym_id', 'check_in_at']);
            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_attendances');
    }
};
