@extends('layouts.app')

@section('title', 'لیستی فرۆشتنەکان و پسووڵەکان - ' . ($shop->name ?? 'GymPro Store'))

@section('content')
<div class="space-y-6">

    <!-- سەرپەڕ و دوگمەکانی گەیشتنی خێرا -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-slate-900/90 border border-slate-800 p-6 rounded-3xl shadow-xl backdrop-blur-md">
        <div>
            <div class="flex items-center gap-3">
                <span class="p-3 bg-emerald-500/10 text-emerald-400 rounded-2xl text-2xl border border-emerald-500/20">
                    🧾
                </span>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight">لیستی فرۆشتنەکان و پسووڵەکان</h1>
                    <p class="text-xs text-slate-400 mt-1">
                        بینینی ئەرشیفی تەواوی فاکتۆرەکان، بەدواداچوونی فرۆش، و چاپکردنەوەی پسووڵەی حراری (80mm) بۆ کڕیار و ئەندامان
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <a href="{{ route('pos.index') }}" class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-600/30 transition-all hover:scale-[1.02] active:scale-[0.98]">
                <span>🖥️</span>
                <span>فرۆشتنی نوێ (POS)</span>
            </a>
            <a href="{{ route('analytics.sales') }}" class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-4 py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-sm transition-all hover:scale-[1.02]">
                <span>📊</span>
                <span>داشبۆردی شیکاری</span>
            </a>
        </div>
    </div>

    <!-- کارتەکانی ئاماری خێرا (KPI Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- کارتی کۆی فاکتۆرەکان -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 block mb-1">کۆی فاکتۆرەکان</span>
                <span class="text-2xl font-black text-white font-mono">{{ number_format($totalSalesCount) }}</span>
                <span class="text-[10px] text-slate-500 block mt-1">پسووڵەی تۆمارکراو</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-xl border border-blue-500/20">
                📑
            </div>
        </div>

        <!-- کارتی کۆی داهات -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 block mb-1">کۆی داهاتی فرۆش</span>
                <span class="text-2xl font-black text-emerald-400 font-mono">{{ number_format($totalRevenue) }}</span>
                <span class="text-[10px] text-emerald-500/80 block mt-1 font-bold">دیناری عێراقی</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl border border-emerald-500/20">
                💰
            </div>
        </div>

        <!-- کارتی فرۆشی ئەمڕۆ -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 block mb-1">فرۆشی ئەمڕۆ</span>
                <span class="text-2xl font-black text-amber-400 font-mono">{{ number_format($todayRevenue) }}</span>
                <span class="text-[10px] text-slate-500 block mt-1">لە دەستپێکی ئەمڕۆوە</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl border border-amber-500/20">
                ⚡
            </div>
        </div>

        <!-- کارتی داشکاندن -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 block mb-1">کۆی داشکاندنەکان</span>
                <span class="text-2xl font-black text-purple-400 font-mono">{{ number_format($totalDiscount) }}</span>
                <span class="text-[10px] text-purple-400/80 block mt-1 font-bold">دیناری بەخشراو</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-xl border border-purple-500/20">
                🎁
            </div>
        </div>
    </div>

    <!-- فۆرمی فلتەر و گەڕان -->
    <div class="bg-slate-900/80 border border-slate-800 p-5 rounded-3xl shadow-lg">
        <form method="GET" action="{{ route('pos.invoices.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- گەڕان -->
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">گەڕان بەدوای پسووڵە یان کڕیار</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="ژمارەی فاکتۆر، ناوی ئەندام، مۆبایل..."
                        class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all pl-9">
                    <span class="absolute left-3 top-3 text-slate-500 text-xs">🔍</span>
                </div>
            </div>

            <!-- فلتەری شێوازی پارەدان -->
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">شێوازی پارەدان</label>
                <select name="payment_method" class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500 transition-all">
                    <option value="ALL" {{ request('payment_method') == 'ALL' ? 'selected' : '' }}>هەموو شێوازەکان</option>
                    <option value="CASH" {{ request('payment_method') == 'CASH' ? 'selected' : '' }}>💵 نەقد (کاش)</option>
                    <option value="CARD" {{ request('payment_method') == 'CARD' ? 'selected' : '' }}>💳 کارت / POS</option>
                    <option value="MEMBER_CREDIT" {{ request('payment_method') == 'MEMBER_CREDIT' ? 'selected' : '' }}>⏳ بە قەرز (سەر ئەندام)</option>
                    <option value="SPLIT" {{ request('payment_method') == 'SPLIT' ? 'selected' : '' }}>🔄 نیوە بە نیوە</option>
                </select>
            </div>

            <!-- فلتەری کات -->
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">ماوەی کاتی</label>
                <select name="date_filter" class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500 transition-all">
                    <option value="all" {{ request('date_filter') == 'all' ? 'selected' : '' }}>هەموو کاتەکان</option>
                    <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>ئەمڕۆ (Today)</option>
                    <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>ئەم هەفتەیە</option>
                    <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>ئەم مانگە</option>
                </select>
            </div>

            <!-- دوگمەکانی فلتەر -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-4 rounded-2xl text-xs transition-all shadow-md shadow-emerald-600/20">
                    جێبەجێکردنی فلتەر
                </button>
                @if(request()->hasAny(['search', 'payment_method', 'date_filter']))
                    <a href="{{ route('pos.invoices.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2.5 px-4 rounded-2xl text-xs border border-slate-700 transition-all">
                        پاککردنەوە
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- خشتەی هەموو فرۆشتنەکان -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl shadow-xl overflow-hidden backdrop-blur-md">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-black text-white">فاکتۆرەکانی فرۆشتن</span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                    {{ $invoices->total() }} فاکتۆر
                </span>
            </div>
            <span class="text-xs text-slate-500">پێشان دراوە لە نوێترینەوە بۆ کۆنترین</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-400 text-xs border-b border-slate-800">
                        <th class="py-3.5 px-4 font-bold">ژمارەی فاکتۆر</th>
                        <th class="py-3.5 px-4 font-bold">بەروار و کات</th>
                        <th class="py-3.5 px-4 font-bold">کڕیار / ئەندام</th>
                        <th class="py-3.5 px-4 font-bold">کاڵاکانی ناو فاکتۆر</th>
                        <th class="py-3.5 px-4 font-bold">شێوازی پارەدان</th>
                        <th class="py-3.5 px-4 font-bold text-left">کۆی گشتی (IQD)</th>
                        <th class="py-3.5 px-4 font-bold">کاشێر</th>
                        <th class="py-3.5 px-4 font-bold text-center">کردار و چاپ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <!-- ژمارەی فاکتۆر -->
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-black text-slate-200 bg-slate-800/80 border border-slate-700/80 px-2.5 py-1 rounded-xl text-[11px]">
                                    #{{ $inv->invoice_number }}
                                </span>
                            </td>

                            <!-- بەروار و کات -->
                            <td class="py-3.5 px-4 text-slate-400">
                                <div class="font-bold text-slate-300">{{ $inv->created_at->format('Y-m-d') }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $inv->created_at->format('h:i A') }}</div>
                            </td>

                            <!-- کڕیار / ئەندام -->
                            <td class="py-3.5 px-4">
                                @if($inv->member)
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold text-xs border border-emerald-500/20">
                                            👤
                                        </div>
                                        <div>
                                            <div class="font-bold text-white">{{ $inv->member->full_name }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono">{{ $inv->member->member_code }}</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center text-xs">
                                            🛍️
                                        </div>
                                        <span class="text-slate-400 font-bold">میوانی ئاسایی</span>
                                    </div>
                                @endif
                            </td>

                            <!-- کاڵاکان -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-800 text-slate-300 font-bold border border-slate-700/60">
                                        {{ $inv->items->count() }} جۆر
                                    </span>
                                    <span class="text-[11px] text-slate-400 truncate max-w-[200px]" title="@foreach($inv->items as $it){{ $it->variant->product->name_ku ?? $it->variant->product->name_en }} ({{ $it->quantity }}x), @endforeach">
                                        @foreach($inv->items->take(2) as $it)
                                            {{ $it->variant->product->name_ku ?? $it->variant->product->name_en }} ({{ (int)$it->quantity }}x)@if(!$loop->last), @endif
                                        @endforeach
                                        @if($inv->items->count() > 2)...@endif
                                    </span>
                                </div>
                            </td>

                            <!-- شێوازی پارەدان -->
                            <td class="py-3.5 px-4">
                                @if($inv->payment_method === 'CASH')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        💵 نەقد (کاش)
                                    </span>
                                @elseif($inv->payment_method === 'CARD')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                        💳 کارت / POS
                                    </span>
                                @elseif($inv->payment_method === 'MEMBER_CREDIT')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        ⏳ بە قەرز
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                        🔄 {{ $inv->payment_method }}
                                    </span>
                                @endif
                            </td>

                            <!-- کۆی گشتی -->
                            <td class="py-3.5 px-4 text-left">
                                <span class="font-mono font-black text-sm text-emerald-400">
                                    {{ number_format($inv->total_amount) }}
                                </span>
                                @if($inv->discount_amount > 0)
                                    <div class="text-[9.5px] text-red-400 font-mono">
                                        داشکاندن: -{{ number_format($inv->discount_amount) }}
                                    </div>
                                @endif
                            </td>

                            <!-- کاشێر -->
                            <td class="py-3.5 px-4 text-slate-400 font-bold">
                                {{ $inv->cashier->name ?? 'کاشێر' }}
                            </td>

                            <!-- کردار و چاپ -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <!-- دۆخی گەڕانەوە یان دوگمەی گەڕاندنەوە -->
                                    @if($inv->payment_status === 'REFUNDED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-rose-500/10 text-rose-400 font-bold text-[11px] border border-rose-500/20 cursor-help"
                                            title="گەڕێندراوەتەوە لەلایەن: {{ $inv->refund?->authorizer?->name ?? 'بەڕێوەبەر' }} - هۆکار: {{ $inv->refund?->reason ?? 'نادیار' }}">
                                            <span>↩️</span>
                                            <span>گەڕاوەتەوە</span>
                                        </span>
                                    @else
                                        <button type="button" onclick="openRefundModal({{ $inv->id }}, '{{ $inv->invoice_number }}', '{{ number_format($inv->total_amount) }} د.ع', '{{ $inv->member ? $inv->member->full_name : 'کڕیاری ئاسایی (میوان)' }}', '{{ $inv->payment_method }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-rose-500/15 hover:bg-rose-600 text-rose-300 hover:text-white font-bold text-xs border border-rose-500/30 transition-all hover:scale-105 active:scale-95 shadow-sm"
                                            title="گەڕاندنەوەی فاکتۆر بە مۆڵەت">
                                            <span>↩️</span>
                                            <span>گەڕاندنەوە</span>
                                        </button>
                                    @endif

                                    <!-- ناردنی پسووڵە بە واتسئاپ -->
                                    <a href="{{ route('pos.invoices.whatsapp', $inv->id) }}" target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-600 text-emerald-300 hover:text-white font-bold text-xs border border-emerald-500/30 transition-all hover:scale-105 active:scale-95"
                                        title="ناردنی پسووڵە بۆ کڕیار بە واتسئاپ">
                                        <span>📲</span>
                                        <span>واتسئاپ</span>
                                    </a>

                                    <!-- چاپی پسووڵەی حراری (لاپەڕەی تەواو بۆ چاپ) -->
                                    <a href="{{ route('pos.invoices.print', $inv->id) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all hover:scale-105 active:scale-95"
                                        title="چاپکردنی پسووڵەی 80mm">
                                        <span>🖨️</span>
                                        <span>چاپ</span>
                                    </a>

                                    <!-- بینینی پسووڵە بە شێوەی مۆداڵ -->
                                    <button onclick="previewInvoice({{ $inv->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs border border-slate-700 transition-all"
                                        title="پێشبینینی خێرا">
                                        <span>👁️</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">🧾</div>
                                <div class="font-bold text-slate-400 text-sm">هیچ فاکتۆرێکی فرۆشتن نەدۆزرایەوە!</div>
                                <div class="text-xs text-slate-600 mt-1">دەتوانیت لە شاشەی فرۆشتنی POS فرۆشتنی نوێ تۆمار بکەیت</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($invoices->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-800/30">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ================= Thermal Receipt Preview Modal (مۆداڵی پیشاندانی پسووڵەی حراری) ================= -->
<div id="receipt-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm hidden">
    <div class="bg-white text-slate-900 rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl border border-slate-300 flex flex-col max-h-[90vh]">
        <!-- سەردێڕی مۆداڵ -->
        <div class="p-4 bg-slate-100 border-b border-slate-200 flex items-center justify-between no-print">
            <div class="flex items-center gap-2">
                <span class="text-lg">🧾</span>
                <span class="font-black text-sm text-slate-800">پسووڵەی فرۆشتن (80mm)</span>
            </div>
            <div class="flex items-center gap-2">
                <a id="modal-whatsapp-btn" href="#" target="_blank" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 shadow-md transition-all">
                    <span>📲</span>
                    <span>واتسئاپ</span>
                </a>
                <button onclick="printFromModal()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 shadow-md border border-slate-700">
                    <span>🖨️</span>
                    <span>چاپکردن</span>
                </button>
                <button onclick="closeReceiptModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center font-bold text-sm">
                    ✕
                </button>
            </div>
        </div>

        <!-- ناوەڕۆکی پسووڵە بۆ چاپ (80mm Thermal Receipt Structure) -->
        <div id="printable-receipt" class="p-6 overflow-y-auto text-xs bg-white text-slate-900 font-sans" style="direction: rtl;">
            <!-- لۆگۆ و ناوی هۆڵ -->
            <div class="text-center pb-3 border-b border-dashed border-slate-400">
                <h2 id="receipt-gym-name" class="text-base font-black uppercase text-slate-900">GymPro Fitness Club</h2>
                <p id="receipt-shop-name" class="text-[11px] font-bold text-slate-700 mt-0.5">شۆپی تەواوکەری خۆراکی</p>
                <p id="receipt-location" class="text-[10px] text-slate-500">سلێمانی</p>
                <p id="receipt-phone" class="text-[10px] font-mono text-slate-500"></p>
            </div>

            <!-- زانیاری فاکتۆر -->
            <div class="py-2.5 border-b border-dashed border-slate-300 text-[11px] space-y-1">
                <div class="flex justify-between">
                    <span class="text-slate-500">ژمارەی پسووڵە:</span>
                    <span id="receipt-inv-num" class="font-mono font-bold text-slate-900"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">بەروار و کات:</span>
                    <span id="receipt-datetime" class="font-mono font-bold text-slate-800"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">کاشێر:</span>
                    <span id="receipt-cashier" class="font-bold text-slate-800"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">کڕیار:</span>
                    <span id="receipt-customer" class="font-bold text-slate-800"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">شێوازی پارەدان:</span>
                    <span id="receipt-payment-method" class="font-bold text-slate-800"></span>
                </div>
            </div>

            <!-- خشتەی کاڵاکان -->
            <table class="w-full text-right my-2 border-collapse text-[11px]">
                <thead>
                    <tr class="border-b-2 border-slate-900 font-black">
                        <th class="py-1">کاڵا</th>
                        <th class="py-1 text-center">بڕ</th>
                        <th class="py-1 text-left">کۆ</th>
                    </tr>
                </thead>
                <tbody id="receipt-items-body" class="divide-y divide-dashed divide-slate-200">
                    <!-- بە شێوەی دینامیکی پڕ دەکرێتەوە -->
                </tbody>
            </table>

            <!-- کۆتایی و پارەدان -->
            <div class="pt-2 border-t-2 border-dashed border-slate-400 space-y-1 text-[11px]">
                <div class="flex justify-between text-slate-600">
                    <span>کۆی سەرەتایی:</span>
                    <span id="receipt-subtotal" class="font-mono font-bold"></span>
                </div>
                <div class="flex justify-between text-red-600 font-bold">
                    <span>داشکاندن:</span>
                    <span id="receipt-discount" class="font-mono"></span>
                </div>
                <div class="flex justify-between text-sm font-black text-slate-900 pt-1 border-t border-slate-900">
                    <span>کۆی گشتی:</span>
                    <span id="receipt-total" class="font-mono text-base"></span>
                </div>
            </div>

            <!-- بارکۆد و هێما -->
            <div class="text-center pt-4 border-t border-dashed border-slate-400 mt-3 space-y-1">
                <div id="receipt-barcode-text" class="font-mono text-xs tracking-widest bg-slate-100 py-1 font-bold">
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
<!-- مۆداڵی گەڕاندنەوەی فاکتۆر بە مۆڵەت (Refund Invoice Modal) -->
<div id="refund-modal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-200">
        <!-- سەردێڕ -->
        <div class="p-5 border-b border-slate-800 flex items-center justify-between bg-rose-500/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-lg border border-rose-500/30">
                    ↩️
                </div>
                <div>
                    <h3 class="font-bold text-white text-base">گەڕاندنەوەی فاکتۆری فرۆشتن</h3>
                    <p class="text-xs text-rose-300">ڕاستکردنەوەی دارایی و گەڕانەوەی کاڵا بۆ کۆگا</p>
                </div>
            </div>
            <button type="button" onclick="closeRefundModal()" class="w-8 h-8 rounded-xl bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-all">
                ✕
            </button>
        </div>

        <!-- فۆرم -->
        <form id="refund-form" method="POST" action="">
            @csrf
            <div class="p-6 space-y-4">
                <!-- کارتی کورتەی فاکتۆر -->
                <div class="bg-slate-800/80 rounded-2xl p-4 border border-slate-700/60 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400">ژمارەی پسووڵە:</span>
                        <span id="refund-inv-number" class="font-mono font-bold text-emerald-400">#POS-000</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400">کڕیار:</span>
                        <span id="refund-customer-name" class="font-bold text-white">کڕیار</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400">بڕی پارەی گەڕاوە:</span>
                        <span id="refund-total-amount" class="font-mono font-black text-rose-400">0 د.ع</span>
                    </div>
                </div>

                <!-- ئاگاداری ڕاستکردنەوەی کۆگا و قەرز -->
                <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-3 text-xs text-amber-300 space-y-1">
                    <div class="flex items-center gap-1.5 font-bold">
                        <span>⚠️</span>
                        <span>مەرج و ئەنجامەکانی گەڕاندنەوە:</span>
                    </div>
                    <ul class="list-disc list-inside text-[11px] text-amber-400/90 pr-2 space-y-0.5">
                        <li>سەرجەم کاڵاکانی ئەم فاکتۆرە بۆ کۆگا و وەجبەکانیان زیاد دەکرێنەوە.</li>
                        <li>ئەگەر بە قەرز فرۆشرابێت، لە قەرزی کڕیار بە شێوەی خۆکار دادەشکێت.</li>
                        <li>ئەم کردارە لە Audit Log بە ناوی بەڕێوەبەر تۆمار دەکرێت.</li>
                    </ul>
                </div>

                <!-- هۆکاری گەڕاندنەوە -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">هۆکاری گەڕاندنەوە <span class="text-rose-500">*</span></label>
                    <textarea name="reason" rows="3" required
                        placeholder="بۆ نموونە: گۆڕینی کاڵا، بەسەرچوونی بەروار، یان پەشیمانبوونەوە..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 transition-all"></textarea>
                </div>
            </div>

            <!-- دوگمەکان -->
            <div class="p-5 border-t border-slate-800 bg-slate-900/50 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeRefundModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-all">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-lg shadow-rose-600/25 transition-all">
                    پەسەندکردنی گەڕاندنەوە
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
    let currentSaleId = null;

    async function previewInvoice(saleId) {
        currentSaleId = saleId;
        const waBtn = document.getElementById('modal-whatsapp-btn');
        if (waBtn) {
            waBtn.href = `/pos/invoices/${saleId}/whatsapp`;
        }
        try {
            const res = await fetch(`/pos/invoices/${saleId}/json`);
            const data = await res.json();
            if (data.success && data.receipt) {
                renderReceiptModal(data.receipt);
            } else {
                alert('هەڵە لە وەرگرتنی زانیاری پسووڵە');
            }
        } catch (e) {
            console.error(e);
            alert('هەڵەیەک لە پەیوەندی بە پسووڵە ڕوویدا');
        }
    }

    function renderReceiptModal(r) {
        document.getElementById('receipt-gym-name').textContent = r.gym_name || 'GymPro Club';
        document.getElementById('receipt-shop-name').textContent = r.shop_name || 'شۆپی هۆڵ';
        document.getElementById('receipt-location').textContent = r.location || 'سلێمانی';
        document.getElementById('receipt-phone').textContent = r.gym_phone ? 'تەلەفۆن: ' + r.gym_phone : '';

        document.getElementById('receipt-inv-num').textContent = '#' + r.invoice_number;
        document.getElementById('receipt-datetime').textContent = `${r.date} ${r.time}`;
        document.getElementById('receipt-cashier').textContent = r.cashier_name;
        document.getElementById('receipt-customer').textContent = `${r.customer_name} (${r.customer_code})`;
        document.getElementById('receipt-payment-method').textContent = r.payment_method;

        // Items table
        const tbody = document.getElementById('receipt-items-body');
        tbody.innerHTML = '';
        r.items.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="py-1 font-bold">${item.name}</td>
                <td class="py-1 text-center font-bold text-slate-700">${item.qty}</td>
                <td class="py-1 text-left font-black font-mono">${item.total.toLocaleString()}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('receipt-subtotal').textContent = r.subtotal.toLocaleString() + ' IQD';
        document.getElementById('receipt-discount').textContent = '-' + r.discount.toLocaleString() + ' IQD';
        document.getElementById('receipt-total').textContent = r.total_amount.toLocaleString() + ' IQD';
        document.getElementById('receipt-barcode-text').textContent = `*${r.invoice_number}*`;

        document.getElementById('receipt-modal').classList.remove('hidden');
    }

    function closeReceiptModal() {
        document.getElementById('receipt-modal').classList.add('hidden');
    }

    function printFromModal() {
        if (currentSaleId) {
            // Open dedicated thermal print view in popup window or print directly
            window.open(`/pos/invoices/${currentSaleId}/print`, '_blank');
        } else {
            window.print();
        }
    }

    function openRefundModal(saleId, invoiceNumber, totalFormatted, customerName, paymentMethod) {
        document.getElementById('refund-form').action = `/pos/invoices/${saleId}/refund`;
        document.getElementById('refund-inv-number').textContent = '#' + invoiceNumber;
        document.getElementById('refund-customer-name').textContent = customerName;
        document.getElementById('refund-total-amount').textContent = totalFormatted;
        document.getElementById('refund-modal').classList.remove('hidden');
    }

    function closeRefundModal() {
        document.getElementById('refund-modal').classList.add('hidden');
    }
</script>
@endpush
