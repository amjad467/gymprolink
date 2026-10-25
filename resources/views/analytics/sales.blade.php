@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Top Bar: Title & Period Filter -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl relative overflow-hidden">
        <div class="absolute -top-24 -left-24 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    داشبۆردی دارایی و شیکاری فرۆشتنەکان
                </span>
                <span class="text-xs text-slate-400 font-bold">• {{ $shop?->name ?? 'شۆپی هۆڵ' }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-white flex items-center gap-2">
                <span>📈 شیکاری تەواوی کڕین و فرۆشتن و داتای کۆگا</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                چاودێری وردی چی فرۆشراوە، بە کێ فرۆشراوە، سەرمایەی کڕین بەرامبەر داهاتی فرۆش، و چاودێری کۆگا و بەسەرچوون
            </p>
        </div>

        <!-- Period Filter & CSV Export -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Period Selector -->
            <div class="flex items-center bg-slate-800 border border-slate-700 rounded-2xl p-1 text-xs font-bold">
                <a href="{{ route('analytics.sales', ['period' => 'today']) }}" class="px-3 py-1.5 rounded-xl transition {{ $period === 'today' ? 'bg-emerald-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
                    ئەمڕۆ
                </a>
                <a href="{{ route('analytics.sales', ['period' => 'week']) }}" class="px-3 py-1.5 rounded-xl transition {{ $period === 'week' ? 'bg-emerald-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
                    ئەم هەفتەیە
                </a>
                <a href="{{ route('analytics.sales', ['period' => 'month']) }}" class="px-3 py-1.5 rounded-xl transition {{ $period === 'month' ? 'bg-emerald-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
                    ئەم مانگە
                </a>
                <a href="{{ route('analytics.sales', ['period' => 'all']) }}" class="px-3 py-1.5 rounded-xl transition {{ $period === 'all' ? 'bg-emerald-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
                    سەرجەم
                </a>
            </div>

            <!-- Export CSV -->
            <a href="{{ route('analytics.sales.export') }}" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs px-3.5 py-2.5 rounded-2xl shadow-lg shadow-emerald-600/20 transition flex items-center gap-1.5">
                <span>📥</span>
                <span>هەناردەکردن (Excel)</span>
            </a>
        </div>
    </div>

    <!-- Main Financial KPI Overview Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
        <!-- 1. Gross Revenue -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-lg space-y-2">
            <span class="text-xs text-slate-400 font-bold block">💵 کۆی داهاتی فرۆش</span>
            <span class="text-lg sm:text-xl font-black text-white font-mono block">
                {{ number_format($totalRevenue) }} <span class="text-xs font-normal text-slate-400">IQD</span>
            </span>
            <span class="text-[11px] text-emerald-400 font-bold block">
                لە {{ $totalSalesCount }} پسووڵەی فرۆشتن
            </span>
        </div>

        <!-- 2. Cost of Goods Sold (تێچووی کڕین) -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-lg space-y-2">
            <span class="text-xs text-slate-400 font-bold block">📦 تێچووی کڕین (سەرمایە)</span>
            <span class="text-lg sm:text-xl font-black text-amber-400 font-mono block">
                {{ number_format($totalCogs) }} <span class="text-xs font-normal text-slate-400">IQD</span>
            </span>
            <span class="text-[11px] text-slate-400 block">
                تێچووی بەرهەمە فرۆشراوەکان
            </span>
        </div>

        <!-- 3. Net Gross Profit (قازانجی نەخالیس) -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-lg space-y-2">
            <span class="text-xs text-slate-400 font-bold block">📈 قازانجی ڕاستەقینە</span>
            <span class="text-lg sm:text-xl font-black text-emerald-400 font-mono block">
                {{ number_format($totalGrossProfit) }} <span class="text-xs font-normal text-slate-400">IQD</span>
            </span>
            <span class="text-[11px] text-emerald-300 font-bold block">
                داهاتی فرۆش نهایز تێچووی کڕین
            </span>
        </div>

        <!-- 4. Profit Margin (%) -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-lg space-y-2">
            <span class="text-xs text-slate-400 font-bold block">📊 ڕێژەی قازانج (Margin)</span>
            <span class="text-lg sm:text-xl font-black text-indigo-400 font-mono block">
                %{{ $profitMargin }}
            </span>
            <span class="text-[11px] text-indigo-300 block">
                سەدی چەندی فرۆش قازانجە
            </span>
        </div>

        <!-- 5. Total Items Sold -->
        <div class="col-span-2 lg:col-span-1 bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-lg space-y-2">
            <span class="text-xs text-slate-400 font-bold block">🛒 دانەی فرۆشراو</span>
            <span class="text-lg sm:text-xl font-black text-teal-300 font-mono block">
                {{ number_format($totalItemsSold) }} <span class="text-xs font-normal text-slate-400">دانە</span>
            </span>
            <span class="text-[11px] text-teal-400 block">
                تەواوی کاڵاکانی دەرچوو لە کۆگا
            </span>
        </div>
    </div>

    <!-- بەشی قازانجی پوختەی ڕاستەقینەی سەرجەم هۆڵ، قەرز و خەرجییەکان (True Net Profit, Expenses & Debts) -->
    <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 border-2 border-emerald-500/30 rounded-3xl p-6 shadow-2xl space-y-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-800 pb-3">
            <div>
                <h2 class="text-base font-black text-white flex items-center gap-2">
                    <span>💎</span>
                    <span>ڕاپۆرتی قازانجی ڕاستەقینەی هۆڵ، قەرزەکان و خەرجی (True Net Profit Analysis)</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    بەستنەوەی قازانجی نەخالیسی مارکێت لەگەڵ داهاتی بەشدارییەکان، لێدەرکردنی خەرجییەکان و چاودێری قەرزی کەڵەکەبوو
                </p>
            </div>
            <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 rounded-xl">
                هاوکێشەی ژمێریاری باوەڕپێکراو
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- ١. قازانجی نەخالیسی مارکێت -->
            <div class="bg-slate-800/60 rounded-2xl p-4 border border-slate-700/60 space-y-1">
                <span class="text-xs text-slate-400 font-bold block">🛍️ قازانجی مارکێت (POS Margin)</span>
                <span class="text-lg font-black text-emerald-400 font-mono block">+{{ number_format($totalGrossProfit) }} IQD</span>
                <span class="text-[11px] text-slate-500 block">داهاتی فرۆش بێ تێچووی کڕین</span>
            </div>

            <!-- ٢. داهاتی بەشدارییەکان -->
            <div class="bg-slate-800/60 rounded-2xl p-4 border border-slate-700/60 space-y-1">
                <span class="text-xs text-slate-400 font-bold block">👥 داهاتی بەشدارییەکان</span>
                <span class="text-lg font-black text-indigo-400 font-mono block">+{{ number_format($membershipRevenue) }} IQD</span>
                <span class="text-[11px] text-slate-500 block">کۆی پارەدانەکانی ئەندامێتی ئەم ماوەیە</span>
            </div>

            <!-- ٣. خەرجییە گشتییەکان -->
            <div class="bg-slate-800/60 rounded-2xl p-4 border border-slate-700/60 space-y-1">
                <span class="text-xs text-slate-400 font-bold block">💸 خەرجییە گشتییەکان</span>
                <span class="text-lg font-black text-rose-400 font-mono block">-{{ number_format($totalExpenses) }} IQD</span>
                <span class="text-[11px] text-slate-500 block">کرێ، مووچە، کارەبا و خەرجی هۆڵ</span>
            </div>

            <!-- ٤. قەرزی نەدراوەکان -->
            <div class="bg-slate-800/60 rounded-2xl p-4 border border-slate-700/60 space-y-1">
                <span class="text-xs text-slate-400 font-bold block">⏳ کۆی قەرزە نەدراوەکان</span>
                <span class="text-lg font-black text-amber-400 font-mono block">{{ number_format($totalDebts) }} IQD</span>
                <span class="text-[11px] text-slate-500 block">قەرزی یاریزانان لەسەر سیستم</span>
            </div>
        </div>

        <!-- کارتی سەرەکی قازانجی پوختەی ڕاستەقینە -->
        <div class="p-4 rounded-2xl bg-gradient-to-r {{ $trueNetProfit >= 0 ? 'from-emerald-950/60 to-slate-900 border border-emerald-500/50' : 'from-rose-950/60 to-slate-900 border border-rose-500/50' }} flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl {{ $trueNetProfit >= 0 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30' }} flex items-center justify-center text-2xl font-black shrink-0">
                    {{ $trueNetProfit >= 0 ? '💰' : '⚠️' }}
                </div>
                <div>
                    <span class="text-xs {{ $trueNetProfit >= 0 ? 'text-emerald-300' : 'text-rose-300' }} font-bold block">
                        قازانجی پوختەی ڕاستەقینەی هۆڵ (Net Bottom-line Profit)
                    </span>
                    <span class="text-[11px] text-slate-400">
                        هاوکێشە: (قازانجی مارکێت + داهاتی بەشداری) - کۆی خەرجییەکان
                    </span>
                </div>
            </div>
            <div class="text-left">
                <span class="text-2xl sm:text-3xl font-black font-mono {{ $trueNetProfit >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ ($trueNetProfit >= 0 ? '+' : '') . number_format($trueNetProfit) }} <span class="text-xs font-normal text-slate-400">IQD</span>
                </span>
            </div>
        </div>
    </div>

    <!-- 2-Columns Grid: Top/Least Sellers & Stock/Expiry Tracking -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Column: Top Sellers vs Low Sellers (6 cols) -->
        <div class="lg:col-span-6 space-y-6">
            
            <!-- Top Selling Products (پڕفرۆشترینەکان) -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🔥</span>
                        <h2 class="text-sm font-black text-white">پڕفرۆشترین کاڵاکان (Top 5 Best Sellers)</h2>
                    </div>
                    <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-lg">
                        زۆرترین خواست
                    </span>
                </div>

                <div class="space-y-2.5">
                    @forelse($topSelling as $rank => $item)
                        <div class="p-3 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-300 font-black text-center flex items-center justify-center font-mono">
                                    {{ $loop->iteration }}
                                </span>
                                <div>
                                    <span class="font-bold text-white block">{{ $item['name'] }}</span>
                                    <span class="text-[11px] text-slate-400 font-mono">{{ $item['sku'] }}</span>
                                </div>
                            </div>
                            <div class="text-left font-mono">
                                <span class="font-black text-emerald-400 text-sm block">{{ $item['quantity'] }} دانە</span>
                                <span class="text-[10px] text-slate-400">{{ number_format($item['revenue']) }} IQD</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-500 text-xs">
                            هیچ تۆمارێکی فرۆشتن لەم ماوەیەدا نییە.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Low Selling / Slow Moving Products (کەمفرۆشترینەکان) -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-base">❄️</span>
                        <h2 class="text-sm font-black text-white">کەمفرۆشترین کاڵاکان (Least Selling / Slow Mover)</h2>
                    </div>
                    <span class="text-xs font-bold text-slate-400 bg-slate-800 px-2 py-0.5 rounded-lg">
                        خواستی کەم
                    </span>
                </div>

                <div class="space-y-2.5">
                    @forelse($leastSelling as $item)
                        <div class="p-3 rounded-2xl bg-slate-800/60 border border-slate-700/50 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-200 block">{{ $item['name'] }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $item['sku'] }}</span>
                            </div>
                            <div class="text-left font-mono">
                                <span class="font-bold text-slate-300 text-xs block">{{ $item['quantity'] }} دانە فرۆشراوە</span>
                                <span class="text-[10px] text-slate-500">{{ number_format($item['revenue']) }} IQD</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-500 text-xs">
                            داتای کەمفرۆش بەردەست نییە.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right Column: Stock Levels & Expiry Warnings (6 cols) -->
        <div class="lg:col-span-6 space-y-6">

            <!-- Stock Levels: Low Stock Alerts & High Stock -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-base">📦</span>
                        <h2 class="text-sm font-black text-white">ئاستی بڕی کۆگا (کەمترین و زۆرترین بڕ)</h2>
                    </div>
                    <span class="text-xs font-bold text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-lg">
                        ئاگاداری تەواوبوون
                    </span>
                </div>

                <!-- Low Stock Alert -->
                <div>
                    <span class="text-xs font-bold text-rose-400 block mb-2 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                        <span>کەمترین بڕ (کەمتر یان یەکسانە بە ٥ دانە - پێویست بە کڕینەوە):</span>
                    </span>
                    <div class="space-y-2">
                        @forelse($lowStockVariants->take(4) as $v)
                            <div class="p-2.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex justify-between items-center text-xs">
                                <div>
                                    <span class="font-bold text-white block">{{ $v->product->name_ku }} {{ $v->flavor ? "({$v->flavor})" : '' }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $v->sku }}</span>
                                </div>
                                <span class="px-2 py-1 rounded-xl bg-rose-600/30 text-rose-300 font-black font-mono">
                                    {{ $v->total_stock }} دانە ماوە!
                                </span>
                            </div>
                        @empty
                            <div class="p-3 rounded-2xl bg-slate-800/40 text-center text-slate-400 text-xs">
                                هەموو کاڵاکان لە ئاستی پێویستی کۆگادان (زیاتر لە ٥ دانە).
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- High Stock Inventory -->
                <div class="pt-2 border-t border-slate-800">
                    <span class="text-xs font-bold text-slate-300 block mb-2">
                        زۆرترین بڕ لە کۆگادا (Top Inventory Stock):
                    </span>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($highStockVariants->take(4) as $hv)
                            <div class="p-2.5 rounded-2xl bg-slate-800 border border-slate-700/60 text-xs">
                                <span class="font-bold text-slate-200 block truncate">{{ $hv->product->name_ku }}</span>
                                <span class="font-black text-emerald-400 font-mono text-sm">{{ $hv->total_stock }} دانە</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Expiry Tracking (FEFO Batches: Expired & Near Expiry) -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-base">⏳</span>
                        <h2 class="text-sm font-black text-white">بەرواری بەسەرچوونی نزیک و بەسەرچوو (FEFO)</h2>
                    </div>
                    <span class="text-xs font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-lg">
                        چاودێری ٩٠ ڕۆژ
                    </span>
                </div>

                <div class="space-y-2.5">
                    <!-- Already Expired Batches -->
                    @if($expiredBatches->count() > 0)
                        <div class="p-3 rounded-2xl bg-rose-500/15 border border-rose-500/40 space-y-1.5">
                            <span class="text-xs font-black text-rose-300 flex items-center gap-1.5">
                                <span>⛔</span>
                                <span>کاڵای بەسەرچوو لە کۆگا (بلۆک کراوە لە فرۆشتن):</span>
                            </span>
                            @foreach($expiredBatches as $eb)
                                <div class="flex justify-between items-center text-xs text-rose-200 border-t border-rose-500/20 pt-1">
                                    <span>{{ $eb->variant->product->name_ku }} (وەجبەی #{{ $eb->batch_number }})</span>
                                    <span class="font-mono font-bold">{{ $eb->quantity_available }} دانە - لە {{ $eb->expiry_date->format('Y-m-d') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Near Expiry Batches (Expiring within 90 days) -->
                    <span class="text-xs font-bold text-slate-300 block">وەجبەکانی نزیک لە بەسەرچوون (FEFO تەرخانکردنی پێشینە):</span>
                    @forelse($nearExpiryBatches->take(4) as $nb)
                        @php
                            $daysLeft = (int) now()->diffInDays($nb->expiry_date, false);
                        @endphp
                        <div class="p-2.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex justify-between items-center text-xs">
                            <div>
                                <span class="font-bold text-white block">{{ $nb->variant->product->name_ku }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">وەجبە: {{ $nb->batch_number }} | بەروار: {{ $nb->expiry_date->format('Y-m-d') }}</span>
                            </div>
                            <div class="text-left font-mono">
                                <span class="text-amber-300 font-bold block">{{ $daysLeft }} ڕۆژی ماوە</span>
                                <span class="text-[11px] text-slate-400 font-bold">{{ $nb->quantity_available }} دانە</span>
                            </div>
                        </div>
                    @empty
                        <div class="p-3 rounded-2xl bg-slate-800/40 text-center text-slate-400 text-xs">
                            هیچ کاڵایەکی نزیک لە بەسەرچوون لە ٩٠ ڕۆژی داهاتوودا نییە.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    <!-- Detailed Sales Log Table: چی فرۆشراوە، بە کێ فرۆشراوە، بە چەند کڕاو و فرۆشراوە -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl space-y-4 p-5">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <span>🧾</span>
                    <span>تۆماری وردی فرۆشتنەکان (چی، بە کێ، و بە چەند فرۆشراوە)</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    هەموو وردەکارییەکی فرۆشتن بە سەرمایەی کڕین (COGS)، نرخی فرۆش، و قازانج بۆ هەر پسووڵەیەک
                </p>
            </div>

            <!-- Quick Search Input -->
            <form action="{{ route('analytics.sales') }}" method="GET" class="w-full sm:w-auto">
                <input type="hidden" name="period" value="{{ $period }}">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 گەڕان بەپێی کڕیار یان ژ. پسووڵە..." class="w-full sm:w-64 bg-slate-800 border border-slate-700 rounded-2xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-950/80 text-slate-400 border-b border-slate-800 text-[11px] font-black uppercase">
                        <th class="p-3">ژ. پسووڵە</th>
                        <th class="p-3">بەروار و کات</th>
                        <th class="p-3">کڕیار (بە کێ فرۆشراوە)</th>
                        <th class="p-3">کاڵا فرۆشراوەکان (چی فرۆشراوە)</th>
                        <th class="p-3 text-center">بڕی دانە</th>
                        <th class="p-3 text-left">تێچووی کڕین (COGS)</th>
                        <th class="p-3 text-left">نرخی فرۆشتن</th>
                        <th class="p-3 text-left">قازانجی نەخالیس</th>
                        <th class="p-3 text-center">شێوازی پارەدان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($salesPaginated as $sale)
                        <tr class="hover:bg-slate-800/40 transition">
                            <!-- Invoice # -->
                            <td class="p-3 font-mono font-black text-emerald-400">
                                #{{ $sale->invoice_number }}
                            </td>

                            <!-- Date & Time -->
                            <td class="p-3 text-slate-300 font-mono text-[11px]">
                                {{ $sale->created_at->format('Y-m-d H:i') }}
                            </td>

                            <!-- Customer (Who) -->
                            <td class="p-3">
                                @if($sale->member)
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-[10px]">👤</span>
                                        <div>
                                            <span class="font-bold text-white block">{{ $sale->member->full_name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $sale->member->member_code }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400 font-bold bg-slate-800 px-2 py-0.5 rounded text-[11px]">
                                        کڕیاری ئاسایی (میوان)
                                    </span>
                                @endif
                            </td>

                            <!-- Items Sold (What) -->
                            <td class="p-3">
                                <div class="space-y-1">
                                    @foreach($sale->items as $it)
                                        <div class="flex items-center gap-1.5 text-[11px]">
                                            <span class="font-bold text-slate-200">
                                                {{ $it->variant->product->name_ku ?? 'کاڵا' }}
                                                @if($it->variant->flavor)
                                                    <span class="text-slate-400 font-normal">({{ $it->variant->flavor }})</span>
                                                @endif
                                            </span>
                                            <span class="text-emerald-400 font-mono font-bold">×{{ $it->quantity }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>

                            <!-- Total Quantity -->
                            <td class="p-3 text-center font-mono font-bold text-slate-300">
                                {{ $sale->items->sum('quantity') }}
                            </td>

                            <!-- Cost Price (تێچووی کڕین) -->
                            <td class="p-3 text-left font-mono text-amber-400">
                                {{ number_format($sale->total_cogs) }} IQD
                            </td>

                            <!-- Sale Price (فرۆشراو) -->
                            <td class="p-3 text-left font-mono font-black text-white">
                                {{ number_format($sale->total_amount) }} IQD
                            </td>

                            <!-- Profit (قازانج) -->
                            <td class="p-3 text-left font-mono font-black text-emerald-400">
                                +{{ number_format($sale->gross_profit) }} IQD
                            </td>

                            <!-- Payment Method -->
                            <td class="p-3 text-center">
                                @php
                                    $methodBadge = match($sale->payment_method) {
                                        'CASH' => ['bg' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30', 'label' => '💵 نەقد'],
                                        'CARD' => ['bg' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30', 'label' => '💳 کارت'],
                                        'MEMBER_CREDIT' => ['bg' => 'bg-amber-500/20 text-amber-300 border-amber-500/30', 'label' => '📝 بە قەرز'],
                                        default => ['bg' => 'bg-slate-700 text-slate-300 border-slate-600', 'label' => $sale->payment_method]
                                    };
                                @endphp
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $methodBadge['bg'] }}">
                                    {{ $methodBadge['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-500 text-xs">
                                هیچ فرۆشتنێک لەم ماوەیەدا نەدۆزرایەوە.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pt-3 border-t border-slate-800">
            {{ $salesPaginated->links() }}
        </div>

    </div>

</div>
@endsection
