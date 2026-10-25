@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- POS Top Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-lg">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2.5 py-0.5 rounded font-bold uppercase bg-indigo-500/20 text-indigo-300">
                    {{ $shop->code }}
                </span>
                <h1 class="text-xl font-black text-white">
                    🛍️ {{ $shop->name }}
                </h1>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                {{ $shop->location_details }} | هۆڵ: <span class="text-slate-300 font-semibold">{{ $activeGym->name }}</span>
            </p>
        </div>

        <!-- Shift & Cashier Status & Navigation -->
        <div class="flex items-center gap-3">
            <a href="{{ route('pos.invoices.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition shadow-sm">
                <span>🧾</span>
                <span>لیستی فرۆشتنەکان</span>
            </a>
            @if($activeShift)
                <div class="text-right">
                    <span class="text-[11px] text-slate-400 block">شفت کراوەیە:</span>
                    <span class="text-xs font-bold text-emerald-400">
                        کاشێر: {{ $activeShift->cashier->name }} (دەستپێک: {{ number_format($activeShift->opening_cash) }} IQD)
                    </span>
                </div>
                <button onclick="openCloseShiftModal()" class="bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition">
                    🔒 داخستنی شفت
                </button>
            @else
                <div class="bg-amber-500/10 border border-amber-500/30 text-amber-300 px-3 py-1.5 rounded-xl text-xs font-bold">
                    ⚠️ هیچ شفتێکی کراوە نییە! تکایە شفت دەستپێبکە.
                </div>
            @endif
        </div>
    </div>

    <!-- POS Layout: Left Product Catalog (7 cols), Right Cart (5 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Product Catalog (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
            <!-- Search & Barcode Input -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-md flex gap-2">
                <input type="text" id="pos-search" oninput="filterProducts()" placeholder="گەڕان بەپێی ناوی کاڵا، بارکۆد، SKU، یان تام..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>

            <!-- Products Grid -->
            <div id="products-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[600px] overflow-y-auto pr-1">
                @foreach($variants as $v)
                    @php
                        $inStock = $v->available_stock > 0;
                    @endphp
                    <div class="product-card bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col justify-between hover:border-slate-700 transition relative {{ !$inStock ? 'opacity-50 grayscale' : '' }}" 
                         data-search="{{ strtolower($v->product->name_ku . ' ' . $v->product->name_en . ' ' . $v->barcode . ' ' . $v->flavor . ' ' . $v->sku) }}">
                        <div>
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-[11px] font-semibold text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded">
                                    {{ $v->product->brand?->name ?? 'Supplement' }}
                                </span>
                                @if($inStock)
                                    <span class="text-[11px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded">
                                        {{ $v->available_stock }} دانە لە کۆگا
                                    </span>
                                @else
                                    <span class="text-[11px] font-bold text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded">
                                        تەواوبووە
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-sm font-bold text-white mt-2">
                                {{ $v->product->name_ku }}
                            </h3>
                            <span class="text-xs text-slate-400 block font-mono">{{ $v->product->name_en }}</span>

                            <div class="flex items-center gap-2 mt-2 text-xs text-slate-300">
                                @if($v->flavor)
                                    <span class="bg-slate-800 px-2 py-0.5 rounded text-[11px]">🍦 {{ $v->flavor }}</span>
                                @endif
                                @if($v->size)
                                    <span class="bg-slate-800 px-2 py-0.5 rounded text-[11px]">⚖️ {{ $v->size }}</span>
                                @endif
                            </div>

                            <div class="mt-2 text-[10px] text-slate-500 font-mono">
                                بارکۆد: {{ $v->barcode }}
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800 flex justify-between items-center">
                            <div class="product-price-box" data-regular="{{ $v->default_sale_price }}" data-member="{{ $v->member_sale_price ?? $v->default_sale_price }}">
                                <div class="price-normal">
                                    <span class="text-base font-black text-white block">
                                        {{ number_format($v->default_sale_price) }} <span class="text-xs font-normal text-slate-400">IQD</span>
                                    </span>
                                    @if($v->member_sale_price && $v->member_sale_price < $v->default_sale_price)
                                        <span class="text-[11px] text-emerald-400 block font-semibold">
                                            بۆ ئەندامان: {{ number_format($v->member_sale_price) }} IQD
                                        </span>
                                    @endif
                                </div>
                                <div class="price-member hidden">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-base font-black text-emerald-400 block font-mono">
                                            {{ number_format($v->member_sale_price ?? $v->default_sale_price) }} <span class="text-xs font-normal text-emerald-300">IQD</span>
                                        </span>
                                        <span class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-1.5 py-0.5 rounded font-bold">
                                            نرخی ئەندام
                                        </span>
                                    </div>
                                    @if($v->member_sale_price && $v->member_sale_price < $v->default_sale_price)
                                        <span class="text-[11px] text-slate-500 line-through block font-mono">
                                            ئاسایی: {{ number_format($v->default_sale_price) }} IQD
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <button onclick="addToCart({{ $v->id }}, '{{ addslashes($v->product->name_ku) }} - {{ $v->flavor }}', {{ $v->default_sale_price }}, {{ $v->member_sale_price ?? $v->default_sale_price }}, {{ $v->available_stock }})" 
                                    class="bg-indigo-600 hover:bg-indigo-500 disabled:bg-slate-800 text-white font-bold px-3 py-1.5 rounded-xl text-xs transition"
                                    {{ !$inStock ? 'disabled' : '' }}>
                                + زیادکردن
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- POS Cart & Checkout (5 cols) -->
        <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-2xl flex flex-col justify-between min-h-[580px]">
            <div>
                <!-- Cart Header -->
                <div class="flex justify-between items-center border-b border-slate-800 pb-3 mb-3">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🛒 سەبەتەی فرۆشتن (Cart)</span>
                    </h2>
                    <button onclick="clearCart()" class="text-xs text-rose-400 hover:text-rose-300 font-semibold">
                        سڕینەوەی هەموو
                    </button>
                </div>

                <!-- Select Member (بۆ داشکاندنی تایبەتی ئەندامان و فرۆشتن بە قەرز) -->
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-1">
                        <label class="text-xs font-bold text-slate-300">ئەندامی هۆڵ (کڕیار):</label>
                        <button type="button" onclick="clearSelectedMember()" class="text-[11px] text-slate-400 hover:text-rose-400 transition">
                            پاککردنەوە
                        </button>
                    </div>

                    <!-- Quick Member Search / Barcode Scan -->
                    <input type="text" 
                           id="member-quick-scan" 
                           placeholder="🔍 گەڕان یان سکەنی کارتی ئەندام بە بارکۆد..." 
                           oninput="filterOrSelectMember(this.value)" 
                           class="w-full bg-slate-800/90 border border-slate-700 focus:border-indigo-500 rounded-xl px-3 py-1.5 text-xs text-white placeholder-slate-500 mb-2 font-mono">

                    <select id="cart-member" onchange="applyMemberPricing()" class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-indigo-500">
                        <option value="">-- کڕیاری ئاسایی (نائەندام) --</option>
                        @foreach($members as $mem)
                            <option value="{{ $mem->id }}" data-code="{{ $mem->member_code }}" data-name="{{ $mem->full_name }}" data-credit="{{ $mem->allow_credit ? '1' : '0' }}" data-limit="{{ $mem->credit_limit }}" data-debt="{{ $mem->current_debt }}">
                                👤 {{ $mem->full_name }} ({{ $mem->member_code }})
                            </option>
                        @endforeach
                    </select>

                    <!-- Dynamic Member Discount Banner -->
                    <div id="member-discount-banner" class="hidden mt-2.5 p-2.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs font-bold flex items-center justify-between">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>نرخی داشکێنراوی ئەندامێتی چالاکە!</span>
                        </span>
                        <span id="member-name-badge" class="bg-emerald-500/20 px-2 py-0.5 rounded text-[11px] font-mono"></span>
                    </div>
                </div>

                <!-- Cart Items Container -->
                <div id="cart-items" class="space-y-2.5 max-h-[260px] overflow-y-auto pr-1">
                    <div id="cart-empty" class="py-12 text-center text-slate-500 text-xs">
                        سەبەتە بەتاڵە! لە بەشی کاڵاکان کلیک لەسەر کاڵاکان بکە بۆ زیادکردن.
                    </div>
                </div>
            </div>

            <!-- Cart Calculation & Checkout Area -->
            <div class="border-t border-slate-800 pt-4 space-y-3 mt-4">
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between text-slate-400">
                        <span>کۆی کاڵاکان:</span>
                        <span id="cart-subtotal" class="font-bold text-white">0 IQD</span>
                    </div>
                    <div class="flex justify-between text-emerald-400">
                        <span>داشکاندنی ئەندامێتی:</span>
                        <span id="cart-discount" class="font-bold">-0 IQD</span>
                    </div>
                    <div class="flex justify-between text-sm font-black text-white border-t border-slate-800 pt-2">
                        <span>کۆی گشتی بۆ پارەدان:</span>
                        <span id="cart-total" class="text-lg text-emerald-400">0 IQD</span>
                    </div>
                </div>

                <!-- Payment Methods Selector -->
                <div class="grid grid-cols-3 gap-2 pt-1">
                    <label class="payment-method-btn flex items-center justify-center gap-1.5 p-2 rounded-xl border border-slate-700 bg-slate-800 cursor-pointer text-xs font-bold text-white">
                        <input type="radio" name="payment_method" value="CASH" checked class="hidden">
                        💵 نەقد
                    </label>
                    <label class="payment-method-btn flex items-center justify-center gap-1.5 p-2 rounded-xl border border-slate-700 bg-slate-800 cursor-pointer text-xs font-bold text-slate-400">
                        <input type="radio" name="payment_method" value="CARD" class="hidden">
                        💳 کارت
                    </label>
                    <label class="payment-method-btn flex items-center justify-center gap-1.5 p-2 rounded-xl border border-slate-700 bg-slate-800 cursor-pointer text-xs font-bold text-slate-400">
                        <input type="radio" name="payment_method" value="MEMBER_CREDIT" class="hidden">
                        📝 بە قەرز
                    </label>
                </div>

                <!-- Complete Sale Button -->
                <button id="checkout-btn" onclick="executeCheckout()" disabled class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-800 disabled:cursor-not-allowed text-white font-black py-3 rounded-2xl text-sm shadow-xl shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                    <span>✓ تەواوکردنی فرۆشتن و دەرکردنی پسووڵە</span>
                </button>
            </div>
        </div>

    </div>

</div>

<!-- Modal: Close Cash Shift with Blind Count -->
<div id="close-shift-modal" class="fixed inset-0 bg-black/75 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4">
        <h3 class="text-base font-bold text-white">🔒 داخستنی شفتی سندوق (Blind Reconciliation)</h3>
        <p class="text-xs text-slate-400">
            تکایە بە شێوەیەکی فیعلی پارەی ناو سندوق بژمێرە و بڕەکەی داخڵ بکە بەبێ دەستکاریکردن:
        </p>

        <form id="close-shift-form" onsubmit="submitCloseShift(event)" class="space-y-3">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">بڕی پارەی ژمێردراوی ناو سندوق (IQD):</label>
                <input type="number" id="actual-cash" step="0.01" required placeholder="بۆ نموونە: 150000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold text-sm focus:outline-none focus:border-rose-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">تێبینی یان هۆکاری کورتێنان/زیادە:</label>
                <textarea id="shift-notes" rows="2" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-rose-500"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeCloseShiftModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500">
                    داخستنی شفت
                </button>
            </div>
        </form>
<!-- Modal: Thermal Sales Receipt (پسووڵەی فرۆشتنی شۆپ) -->
<div id="receipt-modal" class="fixed inset-0 bg-black/85 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="relative max-w-sm w-full my-auto space-y-4">
        
        <!-- Action Buttons (Hidden during actual thermal print) -->
        <div class="flex items-center justify-between gap-2 no-print bg-slate-900 border border-slate-800 p-3 rounded-2xl shadow-xl flex-wrap">
            <button type="button" onclick="printReceipt()" class="flex-1 min-w-[140px] bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs sm:text-sm py-2.5 px-4 rounded-xl shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                <span class="text-base">🖨️</span>
                <span>چاپکردنی پسووڵە</span>
            </button>
            <a id="receipt-whatsapp-link" href="#" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-2.5 px-3.5 rounded-xl transition flex items-center gap-1.5 shadow-md" title="ناردنی پسووڵە بە واتسئاپ بۆ کڕیار">
                <span>📲</span>
                <span>واتسئاپ</span>
            </a>
            <a id="receipt-print-page-link" href="#" target="_blank" class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2.5 px-3.5 rounded-xl transition flex items-center gap-1.5 shadow-md">
                <span>📄</span>
                <span>پەڕەی چاپ</span>
            </a>
            <button type="button" onclick="closeReceiptModal()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs py-2.5 px-4 rounded-xl transition flex items-center gap-1.5">
                <span>✕</span>
                <span>فرۆشتنی نوێ</span>
            </button>
        </div>

        <!-- 80mm Standard Thermal Paper Container -->
        <div id="printable-receipt" class="bg-white text-slate-900 rounded-2xl p-6 shadow-2xl font-mono text-xs border border-slate-200 select-all mx-auto" style="width: 80mm; max-width: 100%;">
            <!-- Receipt Header -->
            <div class="text-center space-y-1 pb-3 border-b-2 border-dashed border-slate-300">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-slate-900 text-white font-black text-lg mb-1">
                    GP
                </div>
                <h2 id="receipt-gym-name" class="font-black text-base tracking-tight text-slate-950">GymPro Fitness Club</h2>
                <p id="receipt-shop-name" class="text-[11px] font-bold text-slate-600">شۆپی تەواوکەری خۆراکی</p>
                <p id="receipt-location" class="text-[10px] text-slate-500">سلێمانی</p>
                <p id="receipt-phone" class="text-[10px] text-slate-600 font-bold dir-ltr">0770 000 0000</p>
            </div>

            <!-- Invoice Meta Info -->
            <div class="py-2.5 border-b border-dashed border-slate-300 space-y-1 text-[11px]">
                <div class="flex justify-between">
                    <span class="text-slate-500">ژ. پسووڵە:</span>
                    <span id="receipt-inv-num" class="font-black text-slate-950">POS-000000</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">بەروار و کات:</span>
                    <span id="receipt-datetime" class="font-bold text-slate-800">2026-10-21 00:00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">کاشێر:</span>
                    <span id="receipt-cashier" class="font-bold text-slate-800">کاشێر</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">کڕیار:</span>
                    <span id="receipt-customer" class="font-black text-slate-950">کڕیاری ئاسایی</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">شێوازی پارەدان:</span>
                    <span id="receipt-payment-method" class="font-black px-1.5 py-0.2 rounded bg-slate-100 text-slate-800">نەقد (کاش)</span>
                </div>
            </div>

            <!-- Items Table -->
            <div class="py-3 border-b-2 border-dashed border-slate-300">
                <table class="w-full text-right text-[11px]">
                    <thead>
                        <tr class="border-b border-slate-300 text-slate-500 pb-1">
                            <th class="py-1 text-right">کاڵا</th>
                            <th class="py-1 text-center">دانە</th>
                            <th class="py-1 text-left">کۆی گشتی</th>
                        </tr>
                    </thead>
                    <tbody id="receipt-items-body" class="divide-y divide-slate-100">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>

            <!-- Calculation Totals -->
            <div class="py-2.5 space-y-1.5 border-b-2 border-dashed border-slate-300 text-[11px]">
                <div class="flex justify-between text-slate-600">
                    <span>کۆی کاڵاکان:</span>
                    <span id="receipt-subtotal" class="font-bold">0 IQD</span>
                </div>
                <div class="flex justify-between text-emerald-700">
                    <span>داشکاندنی ئەندامێتی:</span>
                    <span id="receipt-discount" class="font-bold">-0 IQD</span>
                </div>
                <div class="flex justify-between text-sm font-black text-slate-950 pt-1 border-t border-slate-200">
                    <span>کۆی گشتی بۆ دان:</span>
                    <span id="receipt-total" class="text-base font-black">0 IQD</span>
                </div>
            </div>

            <!-- Barcode & Footer Note -->
            <div class="text-center pt-3 space-y-1.5">
                <div class="inline-block py-1 px-3 bg-slate-100 rounded text-[11px] font-mono tracking-widest font-black text-slate-800 border border-slate-200" id="receipt-barcode-text">
                    *POS-000000*
                </div>
                <p class="text-[10px] text-slate-600 font-bold">
                    سوپاس بۆ سەردانەکەت! بەخێربێنەوە بۆ هۆڵەکەمان
                </p>
                <p class="text-[9px] text-slate-400">
                    سیستەمی بەڕێوەبردنی GymPro Cloud
                </p>
            </div>
        </div>
    </div>
<!-- مۆداڵی داخستنی شفت و بەراوردی پارەی ژمێردراو بە سیستەم (Cash Drawer Reconciliation Modal) -->
<div id="close-shift-modal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-200">
        <!-- سەردێڕ -->
        <div class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-800/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-lg border border-amber-500/30">
                    🔒
                </div>
                <div>
                    <h3 class="font-bold text-white text-base">داخستنی شفتی سندوق</h3>
                    <p class="text-xs text-slate-400">بەراوردی پارەی ژمێردراوی دەست بە پێشبینیکراوی سیستەم</p>
                </div>
            </div>
            <button type="button" onclick="closeCloseShiftModal()" class="w-8 h-8 rounded-xl bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-all">
                ✕
            </button>
        </div>

        <form onsubmit="submitCloseShift(event)" class="p-6 space-y-4">
            <!-- زانیاری شفتی ئێستا -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-slate-800/80 rounded-2xl p-3 border border-slate-700/60">
                    <span class="text-[11px] text-slate-400 block mb-1">کاشێری ئەم شفتە:</span>
                    <span class="text-xs font-bold text-white">{{ $activeShift?->cashier?->name ?? 'کاشێر' }}</span>
                </div>
                <div class="bg-slate-800/80 rounded-2xl p-3 border border-slate-700/60">
                    <span class="text-[11px] text-slate-400 block mb-1">سەرمایەی سەرەتا (Opening):</span>
                    <span class="text-xs font-bold font-mono text-indigo-400">{{ number_format($activeShift?->opening_cash ?? 0) }} IQD</span>
                </div>
            </div>

            <!-- پارەی پێشبینیکراوی سیستەم -->
            <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-2xl p-4 flex items-center justify-between">
                <div>
                    <span class="text-xs text-indigo-300 font-bold block">پارەی پێشبینیکراوی ناو سندوق (سیستەم):</span>
                    <span class="text-[11px] text-indigo-400/80">کۆی نەقدی فرۆشتن + سەرمایەی سەرەتایی</span>
                </div>
                <span id="shift-expected-display" class="font-mono font-black text-lg text-indigo-300">
                    {{ number_format($activeShift?->expected_cash ?? 0) }} IQD
                </span>
            </div>

            <!-- پارەی ژمێردراوی کاشێر (Actual Cash Counted) -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">
                    پارەی ژمێردراوی دەستی ناو سندوق (دینار) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" step="any" min="0" id="actual-cash" required
                        placeholder="بڕی پارەی ژمێردراو بنووسە..."
                        oninput="updateReconciliationDiff(this.value)"
                        class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-base font-mono font-bold text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all pl-12">
                    <span class="absolute left-4 top-3.5 text-xs text-slate-400 font-bold">IQD</span>
                </div>
            </div>

            <!-- بەراورد و جیاوازی زیندوو (Difference Tracker) -->
            <div id="diff-container" class="hidden rounded-2xl p-3.5 border transition-all">
                <div class="flex justify-between items-center text-xs font-bold">
                    <span id="diff-label">جیاوازی ژمێردراو و سیستەم:</span>
                    <span id="diff-amount" class="font-mono font-black text-sm">0 IQD</span>
                </div>
                <p id="diff-subtext" class="text-[11px] mt-1"></p>
            </div>

            <!-- تێبینی داخستن -->
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-1">تێبینی یان هۆکاری جیاوازی (ئارەزوومەندانە):</label>
                <textarea id="shift-notes" rows="2" placeholder="هەر تێبینییەک لەسەر داخستنی سندوق..."
                    class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all"></textarea>
            </div>

            <!-- دوگمەکان -->
            <div class="pt-2 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeCloseShiftModal()" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-all">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-lg shadow-rose-600/25 transition-all">
                    پەسەندکردن و داخستنی شفت
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #receipt-modal,
    #printable-receipt,
    #printable-receipt * {
        visibility: visible !important;
    }
    #receipt-modal {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background: white !important;
        display: flex !important;
        justify-content: center !important;
        align-items: flex-start !important;
        padding: 0 !important;
        margin: 0 !important;
        z-index: 999999 !important;
    }
    #printable-receipt {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 80mm !important;
        max-width: 80mm !important;
        padding: 4mm !important;
        margin: 0 auto !important;
        border: none !important;
        box-shadow: none !important;
        background: white !important;
        color: black !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>
@endsection

@push('scripts')
<script>
    const shopId = "{{ $shop->id }}";
    const cashierId = "{{ $activeShift?->cashier_user_id }}";
    const shiftId = "{{ $activeShift?->id }}";

    let cart = []; // [{variantId, name, regularPrice, memberPrice, quantity, maxStock}]

    function filterProducts() {
        const query = document.getElementById('pos-search').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.product-card');

        cards.forEach(card => {
            const searchData = card.getAttribute('data-search');
            if (!query || searchData.includes(query)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function addToCart(variantId, name, regularPrice, memberPrice, maxStock) {
        const existing = cart.find(i => i.variantId === variantId);
        if (existing) {
            if (existing.quantity < maxStock) {
                existing.quantity++;
            } else {
                alert('بڕی لەمە زیاتر لە کۆگای بەردەستدا نییە.');
                return;
            }
        } else {
            cart.push({
                variantId,
                name,
                regularPrice,
                memberPrice,
                quantity: 1,
                maxStock
            });
        }
        renderCart();
    }

    function updateQty(variantId, delta) {
        const item = cart.find(i => i.variantId === variantId);
        if (!item) return;

        item.quantity += delta;
        if (item.quantity <= 0) {
            cart = cart.filter(i => i.variantId !== variantId);
        } else if (item.quantity > item.maxStock) {
            item.quantity = item.maxStock;
            alert('ئەم بڕە لە کۆگادا بەردەست نییە.');
        }
        renderCart();
    }

    function clearSelectedMember() {
        document.getElementById('cart-member').value = '';
        document.getElementById('member-quick-scan').value = '';
        applyMemberPricing();
    }

    function filterOrSelectMember(query) {
        const select = document.getElementById('cart-member');
        const q = query.trim().toLowerCase();
        if (!q) {
            return;
        }

        // Try exact match on member code or barcode
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            const code = (opt.getAttribute('data-code') || '').toLowerCase();
            const name = (opt.getAttribute('data-name') || '').toLowerCase();
            if (code === q || code.includes(q) || name.includes(q)) {
                select.selectedIndex = i;
                applyMemberPricing();
                break;
            }
        }
    }

    function applyMemberPricing() {
        const memberSelect = document.getElementById('cart-member');
        const isMember = Boolean(memberSelect.value);
        const banner = document.getElementById('member-discount-banner');
        const nameBadge = document.getElementById('member-name-badge');

        if (isMember) {
            const selectedText = memberSelect.options[memberSelect.selectedIndex].text;
            banner.classList.remove('hidden');
            nameBadge.textContent = selectedText.replace('👤', '').trim();
        } else {
            banner.classList.add('hidden');
        }

        // Switch active displayed price on all product cards in catalog
        document.querySelectorAll('.product-price-box').forEach(box => {
            const normalEl = box.querySelector('.price-normal');
            const memberEl = box.querySelector('.price-member');
            if (isMember) {
                normalEl.classList.add('hidden');
                memberEl.classList.remove('hidden');
            } else {
                normalEl.classList.remove('hidden');
                memberEl.classList.add('hidden');
            }
        });

        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cart-items');
        const emptyState = document.getElementById('cart-empty');
        const checkoutBtn = document.getElementById('checkout-btn');
        const memberSelect = document.getElementById('cart-member');
        const isMember = Boolean(memberSelect.value);

        if (cart.length === 0) {
            container.innerHTML = '';
            container.appendChild(emptyState);
            emptyState.style.display = 'block';
            checkoutBtn.disabled = true;
            document.getElementById('cart-subtotal').innerText = '0 IQD';
            document.getElementById('cart-discount').innerText = '-0 IQD';
            document.getElementById('cart-total').innerText = '0 IQD';
            return;
        }

        emptyState.style.display = 'none';
        container.innerHTML = '';

        let subtotal = 0;
        let discount = 0;

        cart.forEach(item => {
            const regularTotal = item.regularPrice * item.quantity;
            const appliedPrice = isMember ? item.memberPrice : item.regularPrice;
            const itemFinalTotal = appliedPrice * item.quantity;
            const itemDiscount = (item.regularPrice - appliedPrice) * item.quantity;

            subtotal += regularTotal;
            discount += (regularTotal - itemFinalTotal);

            const div = document.createElement('div');
            div.className = "p-3 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between text-xs";
            div.innerHTML = `
                <div class="flex-1 pr-2">
                    <span class="font-bold text-white block">${item.name}</span>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="text-[11px] font-mono ${isMember && item.memberPrice < item.regularPrice ? 'text-emerald-400 font-bold' : 'text-slate-400'}">
                            ${appliedPrice.toLocaleString()} IQD × ${item.quantity}
                        </span>
                        ${isMember && item.memberPrice < item.regularPrice ? `
                            <span class="text-[10px] text-slate-500 line-through font-mono">
                                (${item.regularPrice.toLocaleString()} IQD)
                            </span>
                            <span class="text-[10px] px-1 rounded bg-emerald-500/20 text-emerald-300 font-bold">
                                -${itemDiscount.toLocaleString()}
                            </span>
                        ` : ''}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-black text-emerald-400 font-mono ml-2 text-sm">${itemFinalTotal.toLocaleString()} IQD</span>
                    <div class="flex items-center bg-slate-900 border border-slate-700 rounded-lg">
                        <button onclick="updateQty(${item.variantId}, -1)" class="w-6 h-6 flex items-center justify-center text-slate-300 hover:text-white font-bold">-</button>
                        <span class="w-6 text-center text-white font-bold">${item.quantity}</span>
                        <button onclick="updateQty(${item.variantId}, 1)" class="w-6 h-6 flex items-center justify-center text-slate-300 hover:text-white font-bold">+</button>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });

        const total = subtotal - discount;

        document.getElementById('cart-subtotal').innerText = subtotal.toLocaleString() + ' IQD';
        document.getElementById('cart-discount').innerText = '-' + discount.toLocaleString() + ' IQD';
        document.getElementById('cart-total').innerText = total.toLocaleString() + ' IQD';
        checkoutBtn.disabled = false;
    }

    // گۆڕینی شێوازی پارەدان
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.payment-method-btn').forEach(b => {
                b.classList.remove('border-emerald-500', 'text-white');
                b.classList.add('border-slate-700', 'text-slate-400');
            });
            this.classList.remove('border-slate-700', 'text-slate-400');
            this.classList.add('border-emerald-500', 'text-white');
            this.querySelector('input').checked = true;
        });
    });

    async function executeCheckout() {
        const checkoutBtn = document.getElementById('checkout-btn');
        const origBtnText = checkoutBtn.innerHTML;
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = `<span>⏳ لە ئەنجامدان و دەرکردنی پسووڵەدایە...</span>`;

        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        const memberId = document.getElementById('cart-member').value || null;

        const itemsPayload = cart.map(item => {
            const isMember = Boolean(memberId);
            const appliedPrice = isMember ? item.memberPrice : item.regularPrice;
            return {
                variant_id: item.variantId,
                quantity: item.quantity,
                unit_price: appliedPrice
            };
        });

        try {
            const response = await fetch("{{ route('pos.checkout') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    shop_id: parseInt(shopId),
                    items: itemsPayload,
                    payment_method: paymentMethod,
                    member_id: memberId ? parseInt(memberId) : null
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Populate Thermal Receipt Modal
                showReceipt(data.receipt);
                // Clear active cart
                clearCart();
            } else {
                alert('هەڵە لە ئەنجامدانی فرۆشتن: ' + (data.message || 'تکایە دڵنیابە لە بڕی کاڵا لە کۆگا و شفتی کراوە'));
            }
        } catch (err) {
            console.error(err);
            alert('هەڵەیەک لە پەیوەندی سێرڤەر ڕوویدا: ' + err.message);
        } finally {
            checkoutBtn.disabled = false;
            checkoutBtn.innerHTML = origBtnText;
        }
    }

    let lastReceiptData = null;

    function showReceipt(r) {
        lastReceiptData = r;
        if (r.id) {
            const printLink = document.getElementById('receipt-print-page-link');
            if (printLink) {
                printLink.href = `/pos/invoices/${r.id}/print`;
            }
            const waLink = document.getElementById('receipt-whatsapp-link');
            if (waLink) {
                waLink.href = `/pos/invoices/${r.id}/whatsapp`;
            }
        }

        document.getElementById('receipt-gym-name').textContent = r.gym_name || 'GymPro Club';
        document.getElementById('receipt-shop-name').textContent = r.shop_name || 'شۆپی تەواوکەر';
        document.getElementById('receipt-location').textContent = r.location || 'سلێمانی';
        document.getElementById('receipt-phone').textContent = r.gym_phone || '';

        document.getElementById('receipt-inv-num').textContent = r.invoice_number;
        document.getElementById('receipt-datetime').textContent = `${r.date} ${r.time}`;
        document.getElementById('receipt-cashier').textContent = r.cashier_name;
        document.getElementById('receipt-customer').textContent = `${r.customer_name} (${r.customer_code})`;
        document.getElementById('receipt-payment-method').textContent = r.payment_method;

        // Items body
        const tbody = document.getElementById('receipt-items-body');
        tbody.innerHTML = '';
        r.items.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = "border-b border-slate-100";
            tr.innerHTML = `
                <td class="py-1 text-right font-bold">${item.name}</td>
                <td class="py-1 text-center font-bold text-slate-600">${item.qty}</td>
                <td class="py-1 text-left font-black font-mono">${item.total.toLocaleString()} IQD</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('receipt-subtotal').textContent = r.subtotal.toLocaleString() + ' IQD';
        document.getElementById('receipt-discount').textContent = '-' + r.discount.toLocaleString() + ' IQD';
        document.getElementById('receipt-total').textContent = r.total_amount.toLocaleString() + ' IQD';
        document.getElementById('receipt-barcode-text').textContent = `*${r.invoice_number}*`;

        // Display modal
        document.getElementById('receipt-modal').classList.remove('hidden');
    }

    function closeReceiptModal() {
        document.getElementById('receipt-modal').classList.add('hidden');
        clearSelectedMember();
    }

    function printReceipt() {
        if (lastReceiptData && lastReceiptData.id) {
            window.open(`/pos/invoices/${lastReceiptData.id}/print`, '_blank');
        } else {
            window.print();
        }
    }

    const currentExpectedCash = {{ (float) ($activeShift?->expected_cash ?? 0) }};

    function updateReconciliationDiff(val) {
        const container = document.getElementById('diff-container');
        const amountEl = document.getElementById('diff-amount');
        const labelEl = document.getElementById('diff-label');
        const subtextEl = document.getElementById('diff-subtext');

        if (!val || isNaN(val)) {
            container.classList.add('hidden');
            return;
        }

        const counted = parseFloat(val);
        const diff = counted - currentExpectedCash;
        container.classList.remove('hidden');

        if (diff === 0) {
            container.className = 'rounded-2xl p-3.5 border bg-emerald-500/10 border-emerald-500/30 text-emerald-300';
            labelEl.textContent = 'سندوق تەواو و ڕێکخراوە (هاوتا):';
            amountEl.textContent = '0 IQD';
            subtextEl.textContent = 'پارەی ژمێردراو بە تەواوی هاوتای پارەی پێشبینیکراوی سیستەمە.';
        } else if (diff > 0) {
            container.className = 'rounded-2xl p-3.5 border bg-blue-500/10 border-blue-500/30 text-blue-300';
            labelEl.textContent = 'سەرپڕ / پارەی زیادە لە سندوق:';
            amountEl.textContent = '+' + diff.toLocaleString() + ' IQD';
            subtextEl.textContent = 'پارەی دەست لە پێشبینیکراوی سیستەم زیاترە.';
        } else {
            container.className = 'rounded-2xl p-3.5 border bg-rose-500/10 border-rose-500/30 text-rose-300';
            labelEl.textContent = 'کورتهێنان / کەمی سندوق:';
            amountEl.textContent = diff.toLocaleString() + ' IQD';
            subtextEl.textContent = 'ئاگاداری: پارەی ژمێردراوی دەست لە پێشبینیکراوی سیستەم کەمترە.';
        }
    }

    function openCloseShiftModal() {
        document.getElementById('close-shift-modal').classList.remove('hidden');
    }

    function closeCloseShiftModal() {
        document.getElementById('close-shift-modal').classList.add('hidden');
    }

    async function submitCloseShift(e) {
        e.preventDefault();
        const actualCash = document.getElementById('actual-cash').value;
        const notes = document.getElementById('shift-notes').value;

        try {
            const response = await fetch("{{ route('pos.shift.close') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    shift_id: shiftId,
                    actual_cash: parseFloat(actualCash),
                    closing_notes: notes
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const diff = data.shift.cash_difference;
                let diffMsg = diff === 0 ? 'سندوق بە تەواوی هاوتایە.' : (diff < 0 ? `کورتهێنان: ${diff.toLocaleString()} IQD` : `زیادە: ${diff.toLocaleString()} IQD`);
                alert(`شفت بە سەرکەوتوویی داخرا!\n${diffMsg}`);
                window.location.reload();
            } else {
                alert('هەڵە لە داخستنی شفت: ' + data.message);
            }
        } catch (err) {
            console.error(err);
            alert('کێشەیەک ڕوویدا لە داخستنی شفت.');
        }
    }
</script>
@endpush
