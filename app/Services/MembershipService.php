<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipFreeze;
use App\Models\MembershipPlan;
use App\Models\Payment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    /**
     * دروستکردنی ئەندامی نوێ لەگەڵ ناسنامەی بێهاوتا (Member Code)
     */
    public function registerMember(array $data, int $gymId): Member
    {
        return DB::transaction(function () use ($data, $gymId) {
            $count = Member::withoutGlobalScopes()->where('gym_id', $gymId)->count() + 1;
            $memberCode = 'GYM' . str_pad($gymId, 2, '0', STR_PAD_LEFT) . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

            $member = Member::create(array_merge($data, [
                'gym_id' => $gymId,
                'member_code' => $memberCode,
                'status' => 'active',
            ]));

            // دروستکردنی کۆدی QR دەستبەجێ بۆ ئەندام
            app(AccessControlService::class)->generateMemberQrToken($member);

            return $member;
        });
    }

    /**
     * کڕین و بەشداریکردن لە پلانێک بۆ ئەندام لەگەڵ دروستکردنی پسووڵە و پارەدان
     */
    public function subscribeToPlan(
        Member $member,
        MembershipPlan $plan,
        int $createdByUserId,
        float $discount = 0.0,
        ?string $discountReason = null,
        ?int $discountApprovedBy = null,
        float $initialPayment = 0.0,
        string $paymentMethod = 'cash',
        ?string $customStartDate = null,
        ?string $customEndDate = null
    ): Membership {
        return DB::transaction(function () use (
            $member,
            $plan,
            $createdByUserId,
            $discount,
            $discountReason,
            $discountApprovedBy,
            $initialPayment,
            $paymentMethod,
            $customStartDate,
            $customEndDate
        ) {
            $startDate = $customStartDate ? \Carbon\Carbon::parse($customStartDate)->toDateString() : now()->toDateString();
            $endDate = $customEndDate ? \Carbon\Carbon::parse($customEndDate)->toDateString() : \Carbon\Carbon::parse($startDate)->addDays($plan->duration_days)->toDateString();
            $finalPrice = max(0, $plan->price - $discount);

            // دروستکردنی بەشداری
            $membership = Membership::create([
                'gym_id' => $member->gym_id,
                'member_id' => $member->id,
                'membership_plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'original_end_date' => $endDate,
                'visits_limit' => $plan->visits_limit,
                'visits_used' => 0,
                'original_price' => $plan->price,
                'paid_price' => $finalPrice,
                'status' => 'active',
                'created_by_user_id' => $createdByUserId,
            ]);

            // نوێکردنەوەی دۆخی ئەندام بۆ چالاک
            $member->update(['status' => 'active']);

            // دروستکردنی فاکتۆری فەرمی
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $invoice = Invoice::create([
                'gym_id' => $member->gym_id,
                'invoice_number' => $invoiceNumber,
                'member_id' => $member->id,
                'membership_id' => $membership->id,
                'subtotal' => $plan->price,
                'discount_amount' => $discount,
                'discount_reason' => $discountReason,
                'discount_approved_by' => $discountApprovedBy,
                'total_amount' => $finalPrice,
                'paid_amount' => min($initialPayment, $finalPrice),
                'remaining_balance' => max(0, $finalPrice - $initialPayment),
                'status' => ($initialPayment >= $finalPrice) ? 'paid' : (($initialPayment > 0) ? 'partially_paid' : 'due'),
                'created_by_user_id' => $createdByUserId,
                'notes' => "بەشداریکردن لە پلانی {$plan->name}",
            ]);

            // ئەگەر باقیماندە هەبوو، وەک قەرزی ئەندام تۆمار دەکرێت
            if ($invoice->remaining_balance > 0) {
                $member->increment('current_debt', $invoice->remaining_balance);
            }

            // ئەگەر پارەی سەرەتا درابێت
            if ($initialPayment > 0) {
                Payment::create([
                    'gym_id' => $member->gym_id,
                    'invoice_id' => $invoice->id,
                    'member_id' => $member->id,
                    'amount' => min($initialPayment, $finalPrice),
                    'payment_method' => $paymentMethod,
                    'reference_number' => 'RCP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2))),
                    'received_by_user_id' => $createdByUserId,
                    'notes' => 'پارەدانی سەرەتایی لە کاتی ناونووسین',
                ]);
            }

            return $membership;
        });
    }

    /**
     * ڕاگرتنی بەشداری (Membership Freeze)
     */
    public function freezeMembership(
        int $membershipId,
        int $daysCount,
        string $reason,
        int $approvedByUserId
    ): MembershipFreeze {
        return DB::transaction(function () use ($membershipId, $daysCount, $reason, $approvedByUserId) {
            $membership = Membership::findOrFail($membershipId);

            if ($membership->status !== 'active') {
                throw new Exception('تەنها بەشدارییە چالاکەکان دەتوانرێت ڕابگیرێن.');
            }

            $freezeStart = now()->toDateString();
            $freezeEnd = now()->addDays($daysCount)->toDateString();

            // دروستکردنی تۆماری ڕاگرتن
            $freeze = MembershipFreeze::create([
                'gym_id' => $membership->gym_id,
                'membership_id' => $membership->id,
                'freeze_start_date' => $freezeStart,
                'freeze_end_date' => $freezeEnd,
                'days_count' => $daysCount,
                'reason' => $reason,
                'approved_by_user_id' => $approvedByUserId,
                'status' => 'active',
            ]);

            // گۆڕینی دۆخی بەشداری و ئەندام بۆ frozen
            $membership->update(['status' => 'frozen']);
            $membership->member->update(['status' => 'frozen']);

            // تۆمارکردنی Audit Log
            AuditLog::create([
                'gym_id' => $membership->gym_id,
                'user_id' => $approvedByUserId,
                'action' => 'membership.freeze',
                'auditable_type' => Membership::class,
                'auditable_id' => $membership->id,
                'old_values' => ['status' => 'active'],
                'new_values' => [
                    'status' => 'frozen',
                    'freeze_days' => $daysCount,
                    'reason' => $reason,
                ],
            ]);

            return $freeze;
        });
    }

    /**
     * چالاککردنەوەی بەشداری ڕاگیراو و زیادکردنی ڕۆژەکان بۆ بەرواری کۆتایی (Unfreeze)
     */
    public function unfreezeMembership(int $freezeId, int $performedByUserId): Membership
    {
        return DB::transaction(function () use ($freezeId, $performedByUserId) {
            $freeze = MembershipFreeze::findOrFail($freezeId);
            $membership = $freeze->membership;

            if ($freeze->status !== 'active') {
                throw new Exception('ئەم ڕاگرتنە لە پێشتردا تەواوکراوە.');
            }

            // هەژمارکردنی ڕۆژانی ڕاستەقینەی ڕاگرتن
            $actualFrozenDays = max(1, Carbon::parse($freeze->freeze_start_date)->diffInDays(now()));
            $daysToAdd = min($freeze->days_count, $actualFrozenDays);

            // درێژکردنەوەی بەرواری کۆتایی بە ژمارەی ڕۆژە ڕاگیراوەکان
            $newEndDate = $membership->end_date->addDays($daysToAdd);

            $membership->update([
                'status' => 'active',
                'end_date' => $newEndDate,
            ]);

            $membership->member->update(['status' => 'active']);

            $freeze->update([
                'status' => 'completed',
                'freeze_end_date' => now()->toDateString(),
            ]);

            // Audit Log
            AuditLog::create([
                'gym_id' => $membership->gym_id,
                'user_id' => $performedByUserId,
                'action' => 'membership.unfreeze',
                'auditable_type' => Membership::class,
                'auditable_id' => $membership->id,
                'old_values' => ['status' => 'frozen'],
                'new_values' => [
                    'status' => 'active',
                    'days_added' => $daysToAdd,
                    'new_end_date' => $newEndDate->toDateString(),
                ],
            ]);

            return $membership;
        });
    }
}
