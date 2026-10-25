<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕاپۆرتی کۆتایی ڕۆژ (Daily Z-Report) - {{ $selectedDate }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            direction: rtl;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
        }
        .no-print-toolbar {
            width: 80mm;
            max-width: 100%;
            margin-bottom: 15px;
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }
        .btn {
            flex: 1;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            transition: all 0.2s;
        }
        .btn-print {
            background-color: #059669;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(5, 150, 105, 0.3);
        }
        .btn-back {
            background-color: #334155;
            color: #ffffff;
        }
        .receipt-container {
            width: 80mm;
            max-width: 80mm;
            background: #ffffff;
            padding: 6mm 5mm;
            border: 1px dashed #cbd5e1;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            font-size: 11px;
            line-height: 1.4;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .receipt-logo {
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
        }
        .receipt-sub {
            font-size: 13px;
            font-weight: 900;
            color: #0f172a;
            margin-top: 3px;
        }
        .receipt-date {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
            font-family: monospace;
        }
        .section-title {
            font-size: 11px;
            font-weight: 900;
            border-bottom: 1px dashed #94a3b8;
            padding-bottom: 2px;
            margin: 6px 0 4px 0;
            color: #0f172a;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 10.5px;
        }
        .row-bold {
            font-weight: 800;
            color: #0f172a;
        }
        .val {
            font-family: monospace;
            font-weight: 700;
        }
        .total-box {
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
            padding: 4px 0;
            margin: 6px 0;
            font-weight: 900;
            font-size: 12px;
        }
        .highlight-drawer {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 5px;
            margin: 6px 0;
            border-radius: 4px;
        }
        .signature-box {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #94a3b8;
            display: flex;
            justify-content: space-between;
            text-align: center;
            font-size: 9.5px;
            color: #475569;
        }
        .signature-line {
            width: 32mm;
            border-bottom: 1px solid #94a3b8;
            margin-top: 20px;
        }
        @media print {
            body {
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .receipt-container {
                border: none !important;
                box-shadow: none !important;
                width: 80mm !important;
                max-width: 80mm !important;
                padding: 3mm 2mm !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print-toolbar">
        <button onclick="window.print()" class="btn btn-print">
            🖨️ چاپکردنی Z-Report
        </button>
        <a href="{{ route('financial.closing', ['date' => $selectedDate]) }}" class="btn btn-back">
            🔙 گەڕانەوە
        </a>
    </div>

    <div class="receipt-container">
        <div class="receipt-header">
            <div class="receipt-logo">{{ $activeGym->name ?? 'GymPro Club' }}</div>
            <div class="receipt-sub">ڕاپۆرتی داخستنی ڕۆژ (DAILY Z-REPORT)</div>
            <div class="receipt-date">بەرواری حیساب: {{ $selectedDate }} | چاپی کات: {{ now()->format('Y-m-d h:i A') }}</div>
        </div>

        <!-- بەشی بەشدارییەکان -->
        <div class="section-title">١. داهاتی بەشدارییەکان (Memberships)</div>
        <div class="row">
            <span>• نەقد (Cash):</span>
            <span class="val">{{ number_format($membershipCash) }}</span>
        </div>
        <div class="row">
            <span>• کارت / بانک (Card/Bank):</span>
            <span class="val">{{ number_format($membershipCard) }}</span>
        </div>
        <div class="row row-bold">
            <span>کۆی داهاتی بەشداری:</span>
            <span class="val">{{ number_format($membershipTotal) }} IQD</span>
        </div>

        <!-- بەشی فرۆشتنی شۆپ -->
        <div class="section-title">٢. فرۆشتنی شۆپی تەواوکەر (POS Sales)</div>
        <div class="row">
            <span>• نەقد (Cash):</span>
            <span class="val">{{ number_format($posCash) }}</span>
        </div>
        <div class="row">
            <span>• کارت (POS Card):</span>
            <span class="val">{{ number_format($posCard) }}</span>
        </div>
        @if($posCredit > 0)
            <div class="row" style="color: #b45309;">
                <span>• بە قەرز (Member Credit):</span>
                <span class="val">{{ number_format($posCredit) }}</span>
            </div>
        @endif
        <div class="row row-bold">
            <span>کۆی فرۆشی شۆپ:</span>
            <span class="val">{{ number_format($posTotal) }} IQD</span>
        </div>

        <!-- خەرجییەکان -->
        <div class="section-title">٣. خەرجییەکانی ڕۆژەکە (Expenses)</div>
        @forelse($expenses as $exp)
            <div class="row" style="color: #dc2626;">
                <span>• {{ $exp->title }}:</span>
                <span class="val">-{{ number_format($exp->amount) }}</span>
            </div>
        @empty
            <div class="row" style="color: #64748b;">
                <span>هیچ خەرجییەک نەبووە</span>
                <span class="val">0</span>
            </div>
        @endforelse
        <div class="row row-bold" style="color: #dc2626;">
            <span>کۆی گشتی خەرجی:</span>
            <span class="val">-{{ number_format($expensesTotal) }} IQD</span>
        </div>

        <!-- پوختەی سندوق (Cash Drawer Summary) -->
        <div class="highlight-drawer">
            <div class="section-title" style="margin-top: 0;">٤. بەراوردی پارەی دەستی سندوق (Drawer Count)</div>
            <div class="row">
                <span>کۆی نەقدی وەرگیراو:</span>
                <span class="val">{{ number_format($totalCash) }} IQD</span>
            </div>
            <div class="row" style="color: #dc2626;">
                <span>کۆی نەقدی خەرجکراو:</span>
                <span class="val">-{{ number_format($expensesTotal) }} IQD</span>
            </div>
            <div class="row row-bold" style="border-top: 1px dashed #cbd5e1; padding-top: 2px;">
                <span>نەقدی پێشبینیکراو (Expected):</span>
                <span class="val">{{ number_format($netExpectedCash) }} IQD</span>
            </div>
            @if($actualCashCounted !== null && $actualCashCounted !== '')
                <div class="row row-bold">
                    <span>پارەی فیعلی ژمێردراو (Actual):</span>
                    <span class="val">{{ number_format((float)$actualCashCounted) }} IQD</span>
                </div>
                <div class="row row-bold" style="color: {{ $discrepancy < 0 ? '#dc2626' : ($discrepancy > 0 ? '#059669' : '#0f172a') }};">
                    <span>جیاوازی سندوق (Difference):</span>
                    <span class="val">{{ $discrepancy >= 0 ? '+' : '' }}{{ number_format((float)$discrepancy) }} IQD</span>
                </div>
            @endif
        </div>

        <!-- کۆی قازانجی پوختەی ڕۆژەکە -->
        <div class="total-box row">
            <span>قازانجی پوختەی ئەمڕۆ (Net Day Profit):</span>
            <span class="val">{{ number_format($netProfit) }} IQD</span>
        </div>

        @if($notes)
            <div style="font-size: 10px; color: #475569; margin-top: 4px;">
                <strong>تێبینی کاشێر:</strong> {{ urldecode($notes) }}
            </div>
        @endif

        <div class="signature-box">
            <div>
                <div>واژووی کاشێری سندوق</div>
                <div class="signature-line"></div>
            </div>
            <div>
                <div>واژووی بەڕێوەبەری هۆڵ</div>
                <div class="signature-line"></div>
            </div>
        </div>

        <div style="text-align: center; font-size: 8.5px; color: #94a3b8; margin-top: 12px;">
            سیستەمی بەڕێوەبردنی GymPro Cloud • Z-Report Audit
        </div>
    </div>

</body>
</html>
