<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ئامێرەکانی دەرگا و سکەنەر (Access Devices / Turnstiles / Relays)
        Schema::create('access_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->string('name');
            $table->string('device_identifier')->unique(); // ناوی ناسێنەری ئامێر
            $table->enum('device_type', ['qr_scanner', 'barcode_scanner', 'relay_controller', 'turnstile_gate', 'biometric'])->default('qr_scanner');
            $table->enum('direction', ['entry', 'exit', 'bidirectional'])->default('entry');
            $table->string('ip_address')->nullable();
            $table->integer('port')->nullable();
            $table->string('api_key', 64)->unique(); // کلیلی دەستگەیشتنی ئامێر بە Backend API
            $table->string('secret_token', 64);      // تۆکنی واژۆکردنی داواکارییەکان
            $table->integer('relay_pulse_ms')->default(3000); // ماوەی چرکاندنی relay (بۆ نموونە ٣ چرکە بۆ کردنەوەی دەرگا)
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
        });

        // 2. زانیاری و تۆکنەکانی چوونەژوورەوەی ئەندام (Access Credentials - QR / Barcode)
        Schema::create('member_access_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->enum('credential_type', ['qr_code', 'barcode', 'rfid_card'])->default('qr_code');
            $table->string('card_number')->nullable()->index(); // بۆ Barcode یان RFID ئەگەر هەبوو
            $table->string('token_hash', 64)->unique(); // هاشکراوی تۆکنی QR بۆ ڕێگری لە دەستکاری
            $table->string('raw_token')->nullable(); // تۆکنی سەلامەت بۆ دروستکردنەوەی وێنەی QR
            $table->boolean('is_dynamic')->default(false); // ئایا QR بەردەوام دەگۆڕێت بەپێی کات
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->string('revocation_reason')->nullable();
            $table->timestamps();
        });

        // 3. تۆماری سەرجەم سکەنەکان و هەوڵەکانی چوونەژوورەوە (Access Events Log)
        Schema::create('access_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('access_device_id')->nullable()->constrained('access_devices')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->string('scanned_code', 150)->nullable(); // بەشێک لە کۆدی سکەنکراو (بۆ دیباگ بە پاراستنی نهێنی)
            $table->enum('status', ['GRANTED', 'DENIED']);
            $table->string('denial_reason')->nullable(); 
            // هۆکارەکان: invalid_code, member_inactive, membership_expired, membership_frozen, wrong_gym, time_restricted, visits_exhausted, anti_passback, device_disabled
            $table->string('client_ip', 45)->nullable();
            $table->integer('verification_time_ms')->default(0); // خێرایی پشکنین بە میلی چرکە
            $table->timestamp('scanned_at')->index();
            $table->timestamps();

            $table->index(['gym_id', 'status', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_events');
        Schema::dropIfExists('member_access_credentials');
        Schema::dropIfExists('access_devices');
    }
};
