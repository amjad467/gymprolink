<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PosSale;

class WhatsAppService
{
    /**
     * خاوێنکردنەوەی ژمارە تەلەفۆن بۆ فۆرماتی نێودەوڵەتی واتسئاپ (بۆ عێراق 964)
     */
    public function formatPhoneForWhatsApp(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // سڕینەوەی هەموو هێما و بۆشاییەکان
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // ئەگەر بە 00 دەستی پێکردبێت بیگۆڕە
        if (str_starts_with($clean, '00')) {
            $clean = substr($clean, 2);
        }

        // ئەگەر بە 07 دەستی پێکردبێت (وەک 0770XXXXXXX یان 0750XXXXXXX)، بیگۆڕە بۆ 9647XXXXXXXX
        if (str_starts_with($clean, '07')) {
            $clean = '964' . substr($clean, 1);
        } elseif (str_starts_with($clean, '7') && strlen($clean) === 10) {
            $clean = '964' . $clean;
        }

        return $clean;
    }

    /**
     * ئەلیاسی sanitizePhone بۆ formatPhoneForWhatsApp
     */
    public function sanitizePhone(?string $phone): ?string
    {
        return $this->formatPhoneForWhatsApp($phone);
    }

    /**
     * دروستکردنی لینکی ڕاستەوخۆی ناردنی پەیامی واتسئاپ
     */
    public function generateWhatsAppUrl(?string $phone, string $message): string
    {
        $formattedPhone = $this->formatPhoneForWhatsApp($phone);
        $encodedText = rawurlencode($message);

        if ($formattedPhone) {
            return "https://api.whatsapp.com/send?phone={$formattedPhone}&text={$encodedText}";
        }

        return "https://api.whatsapp.com/send?text={$encodedText}";
    }

    /**
     * دروستکردنی دەقی وەسڵی فرۆشتنی شۆپی تەواوکەر بۆ واتسئاپ
     */
    public function buildPosSaleMessage(PosSale $sale): string
    {
        $gymName = $sale->shop->gym->name ?? 'GymPro Club';
        $invNum = $sale->invoice_number;
        $date = $sale->created_at->format('Y-m-d h:i A');
        $customer = $sale->member ? $sale->member->full_name : 'کڕیاری ئاسایی (میوان)';
        $paymentMethods = [
            'CASH' => 'نەقد (کاش)',
            'CARD' => 'کارت / POS',
            'MEMBER_CREDIT' => 'بە قەرز (سەر ئەندام)',
            'SPLIT' => 'نیوە بە نیوە',
        ];
        $payment = $paymentMethods[$sale->payment_method] ?? $sale->payment_method;

        $msg = "🏋️‍♂️ *{$gymName}*\n";
        $msg .= "🛍️ *پسووڵەی کڕین لە شۆپ*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "🧾 ژمارەی پسووڵە: *#{$invNum}*\n";
        $msg .= "👤 کڕیار: *{$customer}*\n";
        $msg .= "📅 کات: {$date}\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "📦 *کاڵاکانی ناو پسووڵە:*\n";

        foreach ($sale->items as $item) {
            $pName = $item->variant->product->name_ku ?? $item->variant->product->name_en ?? 'کاڵا';
            if ($item->variant->flavor) {
                $pName .= " ({$item->variant->flavor})";
            }
            $qty = (int) $item->quantity;
            $total = number_format($item->line_total);
            $msg .= "• {$pName} ({$qty}x) ⇽ *{$total} IQD*\n";
        }

        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        if ($sale->discount_amount > 0) {
            $msg .= "🎁 داشکاندن: -" . number_format($sale->discount_amount) . " IQD\n";
        }
        $msg .= "💰 *کۆی گشتی: " . number_format($sale->total_amount) . " IQD*\n";
        $msg .= "💳 شێوازی پارەدان: {$payment}\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "سوپاس بۆ سەردانەکەت! بەخێربێنەوە بۆ هۆڵەکەمان 🌟💪";

        return $msg;
    }

    /**
     * دروستکردنی دەقی وەسڵی بەشداریکردنی یاریزان بۆ واتسئاپ
     */
    public function buildMembershipReceiptMessage(Member $member, Membership $membership): string
    {
        $gymName = $member->gym->name ?? 'GymPro Fitness Club';
        $planName = $membership->plan->name ?? 'پلانی بەشداری';
        $startDate = $membership->start_date ? $membership->start_date->format('Y-m-d') : now()->toDateString();
        $endDate = $membership->end_date ? $membership->end_date->format('Y-m-d') : 'دیارینەکراو';
        $price = number_format($membership->paid_amount ?? $membership->final_price ?? 0);

        $msg = "🏋️‍♂️ *{$gymName}*\n";
        $msg .= "🏆 *بەخێربێیت بۆ هۆڵەکەمان! وەسڵی بەشداری*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "👤 ناوی یاریزان: *{$member->full_name}*\n";
        $msg .= "🆔 کۆدی یاریزان: *#{$member->member_code}*\n";
        $msg .= "📋 پلانی بەشداری: *{$planName}*\n";
        $msg .= "📅 دەستپێک: {$startDate}\n";
        $msg .= "⏳ بەسەرچوون: *{$endDate}*\n";
        $msg .= "💰 بڕی پارەی دراو: *{$price} IQD*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "هیوای لەشساغی، بەردەوامی و ڕاهێنانێکی سەرکەوتووت بۆ دەخوازین! 💪🔥✨";

        return $msg;
    }

    /**
     * دروستکردنی دەقی ئاگادارکردنەوەی بەسەرچوونی بەشداری (بۆ ٧ ڕۆژ، ٣ ڕۆژ، ١ ڕۆژ پێش بەسەرچوون یان دوای بەسەرچوون)
     */
    public function buildExpiryNoticeMessage(Member $member, ?Membership $membership = null, ?int $daysRemaining = null): string
    {
        $gymName = $member->gym->name ?? 'GymPro Fitness Club';
        $endDate = $membership?->end_date ? $membership->end_date->format('Y-m-d') : 'ئەم چەند ڕۆژە';

        if ($daysRemaining !== null && $daysRemaining > 1) {
            $msg = "سڵاو کاک/خاتوو *{$member->full_name}* ی ئازیز 🌟\n\n";
            $msg .= "ئاگادارت دەکەینەوە لە هۆڵی وەرزشی *{$gymName}* کە بەشدارییەکەت تەنها *{$daysRemaining} ڕۆژ* ی ماوە (بەرواری بەسەرچوون: *{$endDate}*).\n\n";
            $msg .= "بۆ پاراستنی لەشجوانی، بەرنامەی ڕاهێنان و بەردەوامیت، دەتوانیت لە سەردانی داهاتووتدا بەشدارییەکەت نوێ بکەیتەوە! 💪🔥\n\n";
            $msg .= "سوپاس بۆ متمانە و بەردەوامیت 🏋️‍♂️✨";
            return $msg;
        }

        if ($daysRemaining !== null && $daysRemaining <= 1 && $daysRemaining >= 0) {
            $msg = "⚠️ *ئاگاداری بەپەلە لە هۆڵی {$gymName}* ⚠️\n\n";
            $msg .= "سڵاو کاک/خاتوو *{$member->full_name}* ی بەڕێز،\n";
            $msg .= "بەشدارییەکەت سبەی *{$endDate}* بەسەردەچێت!\n\n";
            $msg .= "تکایە پێش بەسەرچوونی کاتەکەت لە ڕیسێپشن نوێی بکەرەوە تاوەکو دەستگەیشتنت بە دەرگای زیرەک و هۆڵەکە بەردەوام بێت. 🏋️‍♂️✨\n";
            return $msg;
        }

        $msg = "سڵاو کاک/خاتوو *{$member->full_name}* ی ئازیز 🌟\n\n";
        $msg .= "ئاگادارت دەکەینەوە لە هۆڵی وەرزشی *{$gymName}* کە بەشدارییەکەت لە بەرواری *{$endDate}* بەسەرچووە.\n\n";
        $msg .= "بۆ پاراستنی لەشجوانی، بەردەوامی و تەندروستیت، بەخۆشحاڵییەوە چاوەڕوانی سەردانەوەتین بۆ نوێکردنەوەی بەشدارییەکەت بە نوێترین ئۆفەری تایبەت! 💪🔥\n\n";
        $msg .= "بەخێربێیتەوە بۆ هۆڵەکەمان 🏋️‍♂️✨";

        return $msg;
    }

    /**
     * دروستکردنی دەقی وەبیرهێنانەوەی قەرز بۆ واتسئاپ
     */
    public function buildDebtReminderMessage(Member $member): string
    {
        $gymName = $member->gym->name ?? 'GymPro Fitness Club';
        $debtFormatted = number_format($member->current_debt);

        $msg = "سڵاو کاک/خاتوو *{$member->full_name}* ی بەڕێز 🌟\n\n";
        $msg .= "هیوادارین لە تەندروستییەکی باشدا بیت.\n";
        $msg .= "لە هۆڵی وەرزشی *{$gymName}*، بەڕێزانە وەبیرهێنانەوەت دەکەین کە بڕی *{$debtFormatted} IQD* قەرزی کۆنی بەشداری یان شۆپ لەسەرت تۆمارە.\n\n";
        $msg .= "تکایە لە کاتی سەردانی داهاتووتدا سندوقی هۆڵەکەمان ئاگادار بکەرەوە بۆ ڕێکخستنی حسابی دارایی.\n\n";
        $msg .= "سوپاس بۆ هاوکاری و بەردەوامیت لەگەڵمان! 🏋️‍♂️💪";

        return $msg;
    }

    /**
     * دروستکردنی دەقی پسووڵەی دانەوەی قەرز بۆ واتسئاپ
     */
    public function buildDebtRepaymentMessage(Payment $payment, Member $member, float $remainingDebt): string
    {
        $gymName = $member->gym->name ?? 'GymPro Fitness Club';
        $amountFormatted = number_format($payment->amount);
        $remainingFormatted = number_format($remainingDebt);
        $date = $payment->created_at ? $payment->created_at->format('Y-m-d h:i A') : now()->format('Y-m-d h:i A');

        $msg = "🧾 *{$gymName}*\n";
        $msg .= "💵 *وەسڵی وەرگرتن و دانەوەی قەرز*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "👤 ناوی یاریزان: *{$member->full_name}*\n";
        $msg .= "🆔 کۆدی یاریزان: *#{$member->member_code}*\n";
        $msg .= "🔢 ژمارەی وەسڵ: *#{$payment->reference_number}*\n";
        $msg .= "📅 بەروار و کات: {$date}\n";
        $msg .= "💰 بڕی وەرگیراو: *{$amountFormatted} IQD*\n";
        $msg .= "⏳ قەرزی ماوەی سەر ئەندام: *{$remainingFormatted} IQD*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "پارەکە بە سەرکەوتوویی لە حسابی سندوق تۆمارکرا. سوپاس بۆ پاکتاوکردنی حسابەکەت! 🌟💪";

        return $msg;
    }
}
