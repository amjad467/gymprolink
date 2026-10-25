<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسووڵەی فرۆشتن #{{ $sale->invoice_number }}</title>
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
        .btn-print:hover {
            background-color: #047857;
        }
        .btn-back {
            background-color: #334155;
            color: #ffffff;
        }
        .btn-back:hover {
            background-color: #1e293b;
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
            border-bottom: 1px dashed #94a3b8;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .receipt-logo {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #0f172a;
            text-transform: uppercase;
        }
        .receipt-sub {
            font-size: 11px;
            font-weight: bold;
            color: #475569;
            margin-top: 2px;
        }
        .receipt-meta-box {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .info-grid {
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 6px;
            margin-bottom: 6px;
            font-size: 10.5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .info-label {
            color: #64748b;
            font-weight: 600;
        }
        .info-value {
            color: #0f172a;
            font-weight: 700;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 11px;
        }
        .items-table th {
            border-bottom: 1.5px solid #0f172a;
            padding: 4px 0;
            font-weight: 800;
            font-size: 10px;
            color: #0f172a;
        }
        .items-table td {
            padding: 4px 0;
            border-bottom: 1px dashed #e2e8f0;
            vertical-align: top;
        }
        .totals-section {
            border-top: 1.5px dashed #0f172a;
            padding-top: 6px;
            margin-bottom: 10px;
            font-size: 11px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .grand-total {
            font-size: 14px;
            font-weight: 900;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
            padding: 5px 0;
            margin-top: 4px;
            color: #0f172a;
        }
        .receipt-footer {
            text-align: center;
            border-top: 1px dashed #94a3b8;
            padding-top: 8px;
            margin-top: 8px;
        }
        .barcode-box {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            letter-spacing: 4px;
            font-weight: bold;
            padding: 3px;
            margin: 4px 0;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }
        .footer-note {
            font-size: 9.5px;
            color: #475569;
            margin-top: 2px;
            font-weight: 600;
        }
        .app-brand {
            font-size: 8.5px;
            color: #94a3b8;
            margin-top: 4px;
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

    <!-- دۆخی دەرەوەی چاپ: دوگمەکانی کرداری خێرا -->
    <div class="no-print-toolbar">
        <button onclick="window.print()" class="btn btn-print">
            🖨️ چاپکردنی پسووڵە
        </button>
        <a href="{{ route('pos.invoices.index') }}" class="btn btn-back">
            📋 لیستی فرۆشتن
        </a>
    </div>

    <!-- پێکهاتەی تەواوی پسووڵەی حراری 80mm -->
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="receipt-logo">{{ $sale->shop->gym->name ?? 'GymPro Club' }}</div>
            <div class="receipt-sub">{{ $sale->shop->name ?? 'شۆپی تەواوکەری خۆراکی' }}</div>
            <div class="receipt-meta-box">
                @if($sale->shop->gym?->phone)
                    <div>تەلەفۆن: {{ $sale->shop->gym->phone }}</div>
                @endif
                <div>{{ $sale->shop->location_details ?? 'سلێمانی - عێراق' }}</div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">ژمارەی فاکتۆر:</span>
                <span class="info-value">#{{ $sale->invoice_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">بەروار و کات:</span>
                <span class="info-value">{{ $sale->created_at->format('Y-m-d') }} {{ $sale->created_at->format('h:i A') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">کاشێر:</span>
                <span class="info-value">{{ $sale->cashier->name ?? 'کاشێری هۆڵ' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">کڕیار:</span>
                <span class="info-value">
                    @if($sale->member)
                        {{ $sale->member->full_name }} ({{ $sale->member->member_code }})
                    @else
                        کڕیاری میوان (ئاسایی)
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">شێوازی پارەدان:</span>
                <span class="info-value">
                    @php
                        $paymentNames = [
                            'CASH' => 'نەقد (کاش)',
                            'CARD' => 'کارت / POS',
                            'MEMBER_CREDIT' => 'بە قەرز (سەر ئەندام)',
                            'SPLIT' => 'نیوە بە نیوە'
                        ];
                    @endphp
                    {{ $paymentNames[$sale->payment_method] ?? $sale->payment_method }}
                </span>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="text-align: right;">کاڵا</th>
                    <th style="text-align: center; width: 12%;">بڕ</th>
                    <th style="text-align: left; width: 35%;">کۆ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    @php
                        $pName = $item->variant->product->name_ku ?? $item->variant->product->name_en ?? 'کاڵا';
                        if ($item->variant->flavor) {
                            $pName .= ' (' . $item->variant->flavor . ')';
                        }
                    @endphp
                    <tr>
                        <td style="text-align: right;">
                            <div style="font-weight: 700;">{{ $pName }}</div>
                            <div style="font-size: 9.5px; color: #64748b; font-family: monospace;">
                                {{ number_format($item->unit_sale_price) }} IQD
                            </div>
                        </td>
                        <td style="text-align: center; font-weight: 800;">
                            {{ (int)$item->quantity }}
                        </td>
                        <td style="text-align: left; font-weight: 900; font-family: monospace;">
                            {{ number_format($item->line_total) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <div class="total-row">
                <span class="info-label">کۆی سەرەتایی:</span>
                <span class="info-value" style="font-family: monospace;">{{ number_format($sale->subtotal) }} IQD</span>
            </div>
            @if($sale->discount_amount > 0)
                <div class="total-row" style="color: #dc2626;">
                    <span>داشکاندن:</span>
                    <span style="font-family: monospace;">-{{ number_format($sale->discount_amount) }} IQD</span>
                </div>
            @endif
            <div class="total-row grand-total">
                <span>کۆی گشتی:</span>
                <span style="font-family: monospace;">{{ number_format($sale->total_amount) }} IQD</span>
            </div>
            @if($sale->payment_method === 'MEMBER_CREDIT')
                <div class="total-row" style="font-size: 10px; color: #b45309; margin-top: 3px;">
                    <span>دۆخی قەرز:</span>
                    <span>سەر حسابی ئەندام تۆمارکرا</span>
                </div>
            @endif
        </div>

        <div class="receipt-footer">
            <div class="barcode-box">*{{ $sale->invoice_number }}*</div>
            <div class="footer-note">سوپاس بۆ سەردانەکەت! بەخێربێنەوە</div>
            <div class="app-brand">سیستەمی بەڕێوەبردنی هۆڵ و فرۆشگای GymPro Cloud</div>
        </div>
    </div>

</body>
</html>
