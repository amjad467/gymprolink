@extends('layouts.app')

@section('title', 'داخستنی دارایی ڕۆژانەی سندوق (Z-Report) - ' . ($activeGym->name ?? 'GymPro'))

@section('content')
<div class="space-y-6">

    <!-- سەرپەڕ و هەڵبژاردنی بەروار -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-slate-900/90 border border-slate-800 p-6 rounded-3xl shadow-xl backdrop-blur-md">
        <div class="flex items-center gap-3">
            <span class="p-3 bg-emerald-500/10 text-emerald-400 rounded-2xl text-2xl border border-emerald-500/20">
                📊
            </span>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">داخستنی دارایی ڕۆژانەی سندوق (Daily Z-Report)</h1>
                <p class="text-xs text-slate-400 mt-1">
                    بەراوردکاری تەواوی داهاتی بەشدارییەکان، فرۆشی شۆپ، خەرجییەکان، و ژماردنی فیعلی سندوق
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <!-- فۆرمی گۆڕینی بەروار -->
            <form method="GET" action="{{ route('financial.closing') }}" class="flex items-center gap-2 bg-slate-800/80 p-1.5 rounded-2xl border border-slate-700">
                <span class="text-xs text-slate-400 font-bold px-2">📅 بەروار:</span>
                <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()"
                    class="bg-slate-900 text-white font-mono text-xs font-bold rounded-xl px-3 py-1.5 border border-slate-700 focus:outline-none focus:border-emerald-500">
            </form>

            <a href="{{ route('expenses.index') }}" class="px-4 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition">
                <span>💸</span>
                <span>خەرجییەکان</span>
            </a>
        </div>
    </div>

    <!-- ٤ پایەی دارایی سەرەکی (Financial Pillars) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- ١. داهاتی بەشداری یاریزانان -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <span class="text-xs font-bold text-blue-400 flex items-center gap-1.5">
                    <span>👥</span>
                    <span>داهاتی بەشدارییەکان</span>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 font-bold border border-blue-500/20">
                    {{ $memberPayments->count() }} وەسڵ
                </span>
            </div>
            <div class="my-3">
                <span class="text-2xl font-black text-white font-mono">{{ number_format($membershipTotal) }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">کۆی بەشداری و نوێکردنەوەکان</span>
            </div>
            <div class="pt-2 border-t border-slate-800/80 text-[11px] space-y-1 text-slate-400">
                <div class="flex justify-between">
                    <span>💵 نەقد (کاش):</span>
                    <span class="font-mono text-slate-200 font-bold">{{ number_format($membershipCash) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>💳 کارت / بانک:</span>
                    <span class="font-mono text-slate-200 font-bold">{{ number_format($membershipCard + $membershipBank) }}</span>
                </div>
            </div>
        </div>

        <!-- ٢. داهاتی شۆپی تەواوکەر -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                    <span>🛍️</span>
                    <span>داهاتی شۆپ (POS)</span>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20">
                    {{ $posSales->count() }} فاکتۆر
                </span>
            </div>
            <div class="my-3">
                <span class="text-2xl font-black text-emerald-400 font-mono">{{ number_format($posTotal) }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">کۆی فرۆشی تەواوکەرەکان</span>
            </div>
            <div class="pt-2 border-t border-slate-800/80 text-[11px] space-y-1 text-slate-400">
                <div class="flex justify-between">
                    <span>💵 نەقد (کاش):</span>
                    <span class="font-mono text-slate-200 font-bold">{{ number_format($posCash) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>💳 کارت / قەرز:</span>
                    <span class="font-mono text-slate-200 font-bold">{{ number_format($posCard + $posCredit) }}</span>
                </div>
            </div>
        </div>

        <!-- ٣. خەرجییەکانی ئەمڕۆ -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <span class="text-xs font-bold text-rose-400 flex items-center gap-1.5">
                    <span>💸</span>
                    <span>خەرجییەکانی ئەمڕۆ</span>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 font-bold border border-rose-500/20">
                    {{ $expenses->count() }} وەسڵ
                </span>
            </div>
            <div class="my-3">
                <span class="text-2xl font-black text-rose-400 font-mono">-{{ number_format($expensesTotal) }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">دراوە لە سندوق یان هۆڵ</span>
            </div>
            <div class="pt-2 border-t border-slate-800/80 text-[11px] text-slate-400">
                <span>تۆمارکراو لەلایەن کاشێر و بەڕێوەبەر</span>
            </div>
        </div>

        <!-- ٤. سافی پارەی ناو سندوق (Net Expected Cash) -->
        <div class="p-5 rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900 to-emerald-950/40 border-2 border-emerald-500/40 flex flex-col justify-between shadow-xl relative overflow-hidden">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <span class="text-xs font-black text-emerald-300 flex items-center gap-1.5">
                    <span>💵</span>
                    <span>سافی پارەی ناو سندوق</span>
                </span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            </div>
            <div class="my-3">
                <span class="text-3xl font-black text-emerald-400 font-mono">{{ number_format($netCashExpected) }}</span>
                <span class="text-[10px] text-emerald-400/80 block mt-0.5 font-bold">دیناری نەقدی پێشبینیکراو</span>
            </div>
            <div class="pt-2 border-t border-slate-800/80 text-[10.5px] text-slate-300">
                <span>(نەقدی بەشداری + نەقدی شۆپ) - خەرجی</span>
            </div>
        </div>
    </div>

    <!-- پانێڵی بەراوردکاری فیعلی سندوق و چاپی فەرمی Z-Report (Cash Reconciliation) -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-slate-800 border-2 border-slate-700/80 rounded-3xl p-6 shadow-2xl">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-800 pb-4 mb-5">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl">🧮</span>
                    <h2 class="text-lg font-black text-white">بەراوردکاری و ژماردنی فیعلی سندوق (Cash Count & Reconciliation)</h2>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    کاشێر پارەی دەستی ناو سندوق دەژمێرێت و داخڵی دەکات تا بزانرێت کورتهێنان یان زیادە هەیە پێش چاپی Z-Report
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="printOfficialZReport()" class="px-6 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-sm shadow-xl shadow-emerald-600/30 transition-all hover:scale-105 active:scale-95 flex items-center gap-2">
                    <span>🖨️</span>
                    <span>داخستنی فەرمی و چاپی Z-Report</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
            <!-- بڕی پێشبینیکراو -->
            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800">
                <span class="text-xs text-slate-400 block mb-1">پارەی نەقدی پێشبینیکراو (Expected):</span>
                <span id="expected-cash-val" data-val="{{ $netCashExpected }}" class="text-xl font-black text-slate-200 font-mono">
                    {{ number_format($netCashExpected) }} IQD
                </span>
            </div>

            <!-- بڕی ژمێردراو لەلایەن کاشێر -->
            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800">
                <label class="text-xs text-emerald-400 font-bold block mb-1">پارەی فیعلی ژمێردراو (Actual Count):</label>
                <input type="number" id="actual-cash-input" value="{{ $netCashExpected }}" oninput="calculateDiscrepancy()"
                    placeholder="بڕی پارەی ژمێردراو..."
                    class="w-full bg-slate-900 border border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-1.5 text-base text-white font-mono font-bold focus:outline-none transition">
            </div>

            <!-- جیاوازی (Discrepancy) -->
            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800">
                <span class="text-xs text-slate-400 block mb-1">جیاوازی سندوق (Discrepancy):</span>
                <span id="discrepancy-label" class="text-xl font-black text-emerald-400 font-mono">
                    0 IQD (هاوتایە ✓)
                </span>
            </div>

            <!-- تێبینی کاشێر -->
            <div>
                <label class="text-xs text-slate-400 block mb-1">تێبینی داخستن (ئارەزوومەندانە):</label>
                <input type="text" id="closing-notes-input" placeholder="تێبینی لەسەر سندوق یان هۆکار..."
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
            </div>
        </div>
    </div>

    <!-- خشتە وردەکانی هەموو مامەڵەکانی ئەمڕۆ (Tabs: بەشداری، فرۆش، خەرجی) -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-md space-y-4">
        
        <!-- Tab Headers -->
        <div class="flex items-center gap-2 border-b border-slate-800 pb-3 text-xs font-bold flex-wrap">
            <button type="button" onclick="showTab('tab-subs')" id="btn-tab-subs" class="px-4 py-2 rounded-xl bg-emerald-600 text-white shadow-md transition flex items-center gap-1.5">
                <span>👥</span>
                <span>پارەدانەکانی بەشداری ({{ $memberPayments->count() }})</span>
            </button>
            <button type="button" onclick="showTab('tab-pos')" id="btn-tab-pos" class="px-4 py-2 rounded-xl text-slate-400 hover:text-white transition flex items-center gap-1.5">
                <span>🛍️</span>
                <span>فرۆشتنەکانی شۆپ ({{ $posSales->count() }})</span>
            </button>
            <button type="button" onclick="showTab('tab-exp')" id="btn-tab-exp" class="px-4 py-2 rounded-xl text-slate-400 hover:text-white transition flex items-center gap-1.5">
                <span>💸</span>
                <span>خەرجییەکان ({{ $expenses->count() }})</span>
            </button>
            <button type="button" onclick="showTab('tab-shifts')" id="btn-tab-shifts" class="px-4 py-2 rounded-xl text-slate-400 hover:text-white transition flex items-center gap-1.5">
                <span>🔒</span>
                <span>شفت و بەراوردی سندوق ({{ $shifts->count() }})</span>
            </button>
        </div>

        <!-- Tab 1: پارەدانەکانی بەشداری ئەمڕۆ -->
        <div id="tab-subs" class="overflow-x-auto">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-400 border-b border-slate-800">
                        <th class="py-3 px-4 font-bold">ناوی یاریزان</th>
                        <th class="py-3 px-4 font-bold">کۆد</th>
                        <th class="py-3 px-4 font-bold">پلانی بەشداری</th>
                        <th class="py-3 px-4 font-bold">شێوازی پارەدان</th>
                        <th class="py-3 px-4 font-bold text-left">بڕی دراو (IQD)</th>
                        <th class="py-3 px-4 font-bold text-center">واتسئاپ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($memberPayments as $p)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-bold text-white">{{ $p->member->full_name ?? 'ـ' }}</td>
                            <td class="py-3 px-4 text-slate-400 font-mono">{{ $p->member->member_code ?? 'ـ' }}</td>
                            <td class="py-3 px-4 text-slate-300">{{ $p->invoice?->membership?->plan?->name ?? 'بەشداری' }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-lg text-[10.5px] font-bold bg-slate-800 text-slate-300">
                                    {{ $p->payment_method === 'cash' ? '💵 نەقد' : ($p->payment_method === 'card' ? '💳 کارت' : '🏦 بانک') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-black text-emerald-400">
                                {{ number_format($p->amount) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($p->member)
                                    <a href="{{ route('members.whatsapp.receipt', $p->member->id) }}" target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 transition text-[11px] font-bold"
                                        title="ناردنی وەسڵی بەشداری بۆ واتسئاپ">
                                        <span>📲</span>
                                        <span>واتسئاپ</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">هیچ پارەدانێکی بەشداری بۆ ئەم بەروارە تۆمار نەکراوە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tab 2: فرۆشتنەکانی شۆپ ئەمڕۆ -->
        <div id="tab-pos" class="overflow-x-auto hidden">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-400 border-b border-slate-800">
                        <th class="py-3 px-4 font-bold">ژ. فاکتۆر</th>
                        <th class="py-3 px-4 font-bold">کڕیار</th>
                        <th class="py-3 px-4 font-bold">کاڵاکان</th>
                        <th class="py-3 px-4 font-bold">شێوازی پارەدان</th>
                        <th class="py-3 px-4 font-bold text-left">کۆی گشتی (IQD)</th>
                        <th class="py-3 px-4 font-bold text-center">واتسئاپ و چاپ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($posSales as $s)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-300">#{{ $s->invoice_number }}</td>
                            <td class="py-3 px-4 text-white font-bold">{{ $s->member ? $s->member->full_name : 'کڕیاری میوان' }}</td>
                            <td class="py-3 px-4 text-slate-400">
                                @foreach($s->items->take(2) as $it)
                                    {{ $it->variant->product->name_ku ?? $it->variant->product->name_en }} ({{ (int)$it->quantity }}x)@if(!$loop->last), @endif
                                @endforeach
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-lg text-[10.5px] font-bold bg-slate-800 text-slate-300">
                                    {{ $s->payment_method }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-black text-emerald-400">
                                {{ number_format($s->total_amount) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('pos.invoices.whatsapp', $s->id) }}" target="_blank"
                                        class="px-2.5 py-1 rounded-xl bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 transition text-[11px] font-bold"
                                        title="ناردنی وەسڵی فرۆشتن بۆ واتسئاپ">
                                        <span>📲</span>
                                    </a>
                                    <a href="{{ route('pos.invoices.print', $s->id) }}" target="_blank"
                                        class="px-2.5 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition text-[11px] font-bold"
                                        title="چاپی پسووڵە">
                                        <span>🖨️</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">هیچ فرۆشتنێکی شۆپ بۆ ئەم بەروارە تۆمار نەکراوە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tab 3: خەرجییەکانی ئەمڕۆ -->
        <div id="tab-exp" class="overflow-x-auto hidden">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-400 border-b border-slate-800">
                        <th class="py-3 px-4 font-bold">بەشی خەرجی</th>
                        <th class="py-3 px-4 font-bold">ناونیشان</th>
                        <th class="py-3 px-4 font-bold">بە کێ دراوە</th>
                        <th class="py-3 px-4 font-bold text-left">بڕ (IQD)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($expenses as $e)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-lg text-[10.5px] font-bold bg-slate-800 text-slate-300">
                                    {{ $e->category }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-bold text-white">{{ $e->title }}</td>
                            <td class="py-3 px-4 text-slate-400">{{ $e->paid_to ?? 'ـ' }}</td>
                            <td class="py-3 px-4 text-left font-mono font-black text-rose-400">
                                -{{ number_format($e->amount) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500">هیچ خەرجییەک بۆ ئەم بەروارە تۆمار نەکراوە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tab 4: شفتەکان و بەراوردی سندوقی کاشێرەکان -->
        <div id="tab-shifts" class="overflow-x-auto hidden">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-400 border-b border-slate-800">
                        <th class="py-3 px-4 font-bold">کاشێر</th>
                        <th class="py-3 px-4 font-bold">دۆخی شفت</th>
                        <th class="py-3 px-4 font-bold">کاتی کردنەوە / داخستن</th>
                        <th class="py-3 px-4 font-bold text-left">سەرمایەی سەرەتا (IQD)</th>
                        <th class="py-3 px-4 font-bold text-left">پێشبینیکراوی سیستەم (IQD)</th>
                        <th class="py-3 px-4 font-bold text-left">ژمێردراوی دەست (IQD)</th>
                        <th class="py-3 px-4 font-bold text-left">جیاوازی سندوق</th>
                        <th class="py-3 px-4 font-bold">تێبینی</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($shifts as $sh)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-bold text-white flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xs">👤</span>
                                <span>{{ $sh->cashier?->name ?? 'کاشێر' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($sh->status === 'OPEN')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span>کراوەیە</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                        <span>🔒 داخراوە</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-400 font-mono text-[11px]">
                                <div>کردنەوە: {{ $sh->opened_at->format('h:i A') }}</div>
                                @if($sh->closed_at)
                                    <div class="text-slate-500">داخستن: {{ $sh->closed_at->format('h:i A') }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-slate-300">
                                {{ number_format($sh->opening_cash) }}
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-indigo-300">
                                {{ number_format($sh->expected_cash) }}
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-white">
                                {{ $sh->actual_cash !== null ? number_format($sh->actual_cash) : 'ـ' }}
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-bold">
                                @if($sh->actual_cash === null)
                                    <span class="text-slate-500 text-[11px]">نەژمێردراوە</span>
                                @elseif($sh->cash_difference == 0)
                                    <span class="px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px]">0 (تەواو ✓)</span>
                                @elseif($sh->cash_difference > 0)
                                    <span class="px-2 py-0.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[11px]">+{{ number_format($sh->cash_difference) }} (زیادە)</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[11px]">{{ number_format($sh->cash_difference) }} (کورتهێنان)</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-400 text-xs">
                                {{ $sh->closing_notes ?? 'ـ' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">هیچ شفتێک بۆ ئەم بەروارە تۆمار نەکراوە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>

@push('scripts')
<script>
    function showTab(tabId) {
        document.getElementById('tab-subs').classList.add('hidden');
        document.getElementById('tab-pos').classList.add('hidden');
        document.getElementById('tab-exp').classList.add('hidden');
        document.getElementById('tab-shifts').classList.add('hidden');

        document.getElementById('btn-tab-subs').className = 'px-4 py-2 rounded-xl text-slate-400 hover:text-white transition flex items-center gap-1.5';
        document.getElementById('btn-tab-pos').className = 'px-4 py-2 rounded-xl text-slate-400 hover:text-white transition flex items-center gap-1.5';
        document.getElementById('btn-tab-exp').className = 'px-4 py-2 rounded-xl text-slate-400 hover:text-white transition flex items-center gap-1.5';
        document.getElementById('btn-tab-shifts').className = 'px-4 py-2 rounded-xl text-slate-400 hover:text-white transition flex items-center gap-1.5';

        document.getElementById(tabId).classList.remove('hidden');

        if (tabId === 'tab-subs') {
            document.getElementById('btn-tab-subs').className = 'px-4 py-2 rounded-xl bg-emerald-600 text-white shadow-md transition flex items-center gap-1.5';
        } else if (tabId === 'tab-pos') {
            document.getElementById('btn-tab-pos').className = 'px-4 py-2 rounded-xl bg-emerald-600 text-white shadow-md transition flex items-center gap-1.5';
        } else if (tabId === 'tab-exp') {
            document.getElementById('btn-tab-exp').className = 'px-4 py-2 rounded-xl bg-rose-600 text-white shadow-md transition flex items-center gap-1.5';
        } else if (tabId === 'tab-shifts') {
            document.getElementById('btn-tab-shifts').className = 'px-4 py-2 rounded-xl bg-indigo-600 text-white shadow-md transition flex items-center gap-1.5';
        }
    }

    function calculateDiscrepancy() {
        const expected = parseFloat(document.getElementById('expected-cash-val').getAttribute('data-val')) || 0;
        const actualInput = document.getElementById('actual-cash-input').value;
        const actual = actualInput === '' ? 0 : parseFloat(actualInput);
        const diff = actual - expected;

        const label = document.getElementById('discrepancy-label');
        if (diff === 0) {
            label.textContent = '0 IQD (هاوتایە بە تەواوی ✓)';
            label.className = 'text-xl font-black text-emerald-400 font-mono';
        } else if (diff < 0) {
            label.textContent = `کورتهێنان: ${Math.abs(diff).toLocaleString()} IQD ⚠️`;
            label.className = 'text-xl font-black text-rose-500 font-mono';
        } else {
            label.textContent = `زیادە: +${diff.toLocaleString()} IQD 🟢`;
            label.className = 'text-xl font-black text-teal-400 font-mono';
        }
    }

    function printOfficialZReport() {
        const date = "{{ $selectedDate }}";
        const actual = document.getElementById('actual-cash-input').value;
        const expected = parseFloat(document.getElementById('expected-cash-val').getAttribute('data-val')) || 0;
        const diff = (parseFloat(actual) || 0) - expected;
        const notes = encodeURIComponent(document.getElementById('closing-notes-input').value);

        const url = `{{ route('financial.closing.print') }}?date=${date}&actual_cash=${actual}&discrepancy=${diff}&notes=${notes}`;
        window.open(url, '_blank');
    }
</script>
@endpush
@endsection
