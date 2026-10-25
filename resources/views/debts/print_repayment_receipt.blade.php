<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>چاپی وەسڵی دانەوەی قەرز #{{ $payment->reference_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif, system-ui;
        }

        body {
            background-color: #f8fafc;
            color: #0f172a;
            direction: rtl;
            text-align: right;
            font-size: 11px;
            line-height: 1.4;
        }

        .no-print-toolbar {
            max-width: 80mm;
            margin: 15px auto;
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-print {
            background-color: #059669;
            color: white;
        }

        .btn-back {
            background-color: #334155;
            color: white;
        }

        .btn-wa {
            background-color: #10b981;
            color: white;
        }

        .receipt-container {
            width: 80mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 6mm 4mm;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #94a3b8;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .receipt-logo {
            font-size: 16px;
            font-weight: 900;
            text-transform: uppercase;
            color: #0f172a;
        }

        .receipt-sub {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            margin-top: 2px;
        }

        .receipt-badge {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-weight: 900;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 6px;
            margin-top: 5px;
        }

        .receipt-phone {
            font-size: 10px;
            font-family: monospace;
            color: #64748b;
            margin-top: 3px;
        }

        .meta-section {
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 6px;
            margin-bottom: 6px;
            font-size: 10.5px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .meta-label {
            color: #64748b;
        }

        .meta-val {
            font-weight: 700;
            color: #0f172a;
        }

        .amount-box {
            background: #f1f5f9;
            border: 2px dashed #059669;
            border-radius: 8px;
            padding: 8px;
            text-align: center;
            margin: 10px 0;
        }

        .amount-label {
            font-size: 11px;
            font-weight: bold;
            color: #059669;
        }

        .amount-val {
            font-size: 18px;
            font-weight: 900;
            font-family: monospace;
            color: #0f172a;
            margin: 2px 0;
        }

        .remaining-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 6px;
            padding: 6px 8px;
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 11px;
            color: #be123c;
            margin-bottom: 10px;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 18px;
            padding-top: 10px;
            border-top: 1px dashed #cbd5e1;
            text-align: center;
            font-size: 10px;
            color: #475569;
        }

        .signature-line {
            margin-top: 25px;
            border-top: 1px solid #0f172a;
        }

        .receipt-footer {
            text-align: center;
            font-size: 8.5px;
            color: #94a3b8;
            margin-top: 14px;
        }

        @media print {
            body {
                background: white !important;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .receipt-container {
                border: none !important;
                box-shadow: none !important;
                width: 80mm !important;
                max-width: 80mm !important;
                padding: 2mm 1mm !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- دوگمەکانی سەرەوە لە دەرەوەی چاپ -->
    <div class="no-print-toolbar">
        <button onclick="window.print()" class="btn btn-print">
            🖨️ چاپکردنی پسووڵە
        </button>
        <a href="{{ route('debts.payments.whatsapp', $payment->id) }}" target="_blank" class="btn btn-wa">
            📲 WhatsApp
        </a>
        <a href="{{ route('debts.index') }}" class="btn btn-back">
            🔙 گەڕانەوە
        </a>
    </div>

    <!-- پێکهاتەی وەسڵی 80mm -->
    <div class="receipt-container">
        <!-- سەردێڕ -->
        <div class="receipt-header">
            <div class="receipt-logo">{{ $gym->name ?? 'GymPro Fitness' }}</div>
            <div class="receipt-sub">وەسڵی وەرگرتن و پاکتاوی قەرز</div>
            <div class="receipt-badge">DEBT SETTLEMENT RECEIPT</div>
            @if($gym->phone)
                <div class="receipt-phone">تەلەفۆن: {{ $gym->phone }}</div>
            @endif
        </div>

        <!-- زانیاری وەسڵ -->
        <div class="meta-section">
            <div class="meta-row">
                <span class="meta-label">ژمارەی پسووڵە:</span>
                <span class="meta-val font-mono">#{{ $payment->reference_number }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">بەروار و کات:</span>
                <span class="meta-val font-mono">{{ $payment->created_at->format('Y-m-d h:i A') }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">کاشێر:</span>
                <span class="meta-val">{{ $payment->receiver->name ?? 'سندوق' }}</span>
            </div>
        </div>

        <!-- زانیاری یاریزان -->
        <div class="meta-section">
            <div class="meta-row">
                <span class="meta-label">ناوی یاریزان:</span>
                <span class="meta-val">{{ $member->full_name }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">کۆدی ئەندام:</span>
                <span class="meta-val font-mono">#{{ $member->member_code }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">مۆبایل:</span>
                <span class="meta-val font-mono">{{ $member->phone }}</span>
            </div>
        </div>

        <!-- بڕی پارەی دراو -->
        <div class="amount-box">
            <div class="amount-label">بڕی پارەی وەرگیراو (IQD)</div>
            <div class="amount-val">{{ number_format($payment->amount) }} IQD</div>
            <div style="font-size: 9.5px; color: #64748b; margin-top: 2px;">
                شێوازی پارەدان: {{ $payment->payment_method === 'cash' || $payment->payment_method === 'debt_settlement' ? 'نەقد (کاش)' : 'کارت / بانک' }}
            </div>
        </div>

        <!-- قەرزی ماوە -->
        <div class="remaining-box">
            <span>قەرزی ماوەی سەر یاریزان:</span>
            <span class="font-mono">{{ number_format($member->current_debt) }} IQD</span>
        </div>

        @if($payment->notes)
            <div style="font-size: 10px; color: #475569; margin-bottom: 6px; padding: 4px; background: #f8fafc; border-radius: 4px;">
                <strong>تێبینی:</strong> {{ $payment->notes }}
            </div>
        @endif

        <!-- واژووەکان -->
        <div class="signature-grid">
            <div>
                <div>واژووی کاشێری سندوق</div>
                <div class="signature-line"></div>
            </div>
            <div>
                <div>واژووی یاریزان</div>
                <div class="signature-line"></div>
            </div>
        </div>

        <!-- پێپەڕە -->
        <div class="receipt-footer">
            سوپاس بۆ پابەندبوونت بە پاکتاوکردنی حسابەکەت لە هۆڵەکەمان!<br>
            سیستەمی بەڕێوەبردنی GymPro Cloud • Electronic Audit Receipt
        </div>
    </div>

</body>
</html>
