@extends('layouts.app')

@section('title', 'بەڕێوەبردنی قەرزەکان و پاکتاوی دارایی - ' . ($activeGym->name ?? 'GymPro'))

@section('content')
<div class="space-y-6">

    <!-- سەرپەڕ و پوختە -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-slate-900/90 border border-slate-800 p-6 rounded-3xl shadow-xl backdrop-blur-md">
        <div class="flex items-center gap-3">
            <span class="p-3 bg-amber-500/10 text-amber-400 rounded-2xl text-2xl border border-amber-500/20">
                💳
            </span>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">بەڕێوەبردنی قەرزەکان و کەشف حیساب</h1>
                <p class="text-xs text-slate-400 mt-1">
                    چاودێریکردنی قەرزی یاریزانان (شۆپ و بەشداری)، وەرگرتنی قیست و دانەوەی قەرز لەگەڵ ئاگاداری ڕاستەوخۆی WhatsApp
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('financial.closing') }}" class="px-4 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition">
                <span>📑</span>
                <span>داخستنی سندوق (Z-Report)</span>
            </a>
            <a href="{{ route('pos.index') }}" class="px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-md shadow-emerald-600/20">
                <span>🛍️</span>
                <span>فرۆشتنی POS</span>
            </a>
        </div>
    </div>

    <!-- پەیامی سەرکەوتنی دوایین دانەوەی قەرز (لەگەڵ چاپی پسووڵە و واتسئاپ) -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs font-bold flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-lg">
            <div class="flex items-center gap-2">
                <span class="text-lg">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            @if(session('last_payment_id'))
                <div class="flex items-center gap-2">
                    <a href="{{ route('debts.payments.print', session('last_payment_id')) }}" target="_blank"
                        class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white border border-slate-700 transition flex items-center gap-1 shadow-sm">
                        <span>🖨️</span>
                        <span>چاپی پسووڵە (80mm)</span>
                    </a>
                    <a href="{{ route('debts.payments.whatsapp', session('last_payment_id')) }}" target="_blank"
                        class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white transition flex items-center gap-1 shadow-sm">
                        <span>📲</span>
                        <span>ناردنی وەسڵ بە WhatsApp</span>
                    </a>
                </div>
            @endif
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-bold flex items-center gap-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- کارتەکانی ئامار (Debt KPI Metrics) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- ١. کۆی گشتی قەرزە نەدراوەکان -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <span class="text-xs font-bold text-rose-400 flex items-center gap-1.5">
                    <span>💳</span>
                    <span>کۆی گشتی قەرزەکان</span>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 font-bold border border-rose-500/20">
                    لای ئەندامان
                </span>
            </div>
            <div class="my-3">
                <span class="text-2xl font-black text-rose-400 font-mono">{{ number_format($totalDebt) }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5 font-sans">دیناری عێراقی (IQD)</span>
            </div>
            <div class="pt-2 border-t border-slate-800 text-[11px] text-slate-500">
                <span>سەرجەم قەرزەکانی شۆپ و بەشداری</span>
            </div>
        </div>

        <!-- ٢. ژمارەی قەرزداران -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <span class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                    <span>👥</span>
                    <span>یاریزانانی قەرزدار</span>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 font-bold border border-amber-500/20">
                    چالاک
                </span>
            </div>
            <div class="my-3">
                <span class="text-2xl font-black text-white font-mono">{{ $debtorCount }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">یاریزان قەرزی لەسەرە</span>
            </div>
            <div class="pt-2 border-t border-slate-800 text-[11px] text-slate-500">
                <span>پێویستە لای دەرگا و شۆپ ئاگادار بکرێنەوە</span>
            </div>
        </div>

        <!-- ٣. قەرزی وەرگیراوەی ئەم مانگە -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                    <span>💰</span>
                    <span>قەرزی دراوەی ئەم مانگە</span>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20">
                    گەڕاوە بۆ سندوق
                </span>
            </div>
            <div class="my-3">
                <span class="text-2xl font-black text-emerald-400 font-mono">{{ number_format($recoveredThisMonth) }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">دینار پاکتاوکراوە لە مانگی {{ now()->format('m') }}</span>
            </div>
            <div class="pt-2 border-t border-slate-800 text-[11px] text-slate-500">
                <span>زیادکراوە بۆ حسابی سندوقی هۆڵ</span>
            </div>
        </div>

        <!-- ٤. زۆرترین قەرزی تاکی -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <span class="text-xs font-bold text-indigo-400 flex items-center gap-1.5">
                    <span>🔝</span>
                    <span>بەرزترین قەرز</span>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 font-bold border border-indigo-500/20">
                    تاکی
                </span>
            </div>
            <div class="my-3">
                <span class="text-2xl font-black text-indigo-300 font-mono">{{ number_format($maxDebt) }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">زۆرترین قەرزی یەک کەس</span>
            </div>
            <div class="pt-2 border-t border-slate-800 text-[11px] text-slate-500">
                <span>بەپێی سنووری متمانەی هۆڵ</span>
            </div>
        </div>
    </div>

    <!-- باڕی فلتەر و گەڕان -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-4 shadow-xl backdrop-blur-md">
        <form method="GET" action="{{ route('debts.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <!-- گەڕان -->
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="🔍 گەڕان بەپێی ناوی یاریزان، ژمارەی مۆبایل، کۆدی یاریزان..."
                    class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-all">
            </div>

            <!-- فلتەری بڕی قەرز -->
            <div class="flex items-center gap-2">
                <select name="range" onchange="this.form.submit()"
                    class="bg-slate-800 border border-slate-700 text-xs text-white rounded-2xl px-3 py-2.5 focus:outline-none focus:border-amber-500 transition">
                    <option value="" {{ request('range') == '' ? 'selected' : '' }}>هەموو قەرزەکان</option>
                    <option value="small" {{ request('range') == 'small' ? 'selected' : '' }}>کەمتر لە 25,000 IQD</option>
                    <option value="medium" {{ request('range') == 'medium' ? 'selected' : '' }}>25,000 بۆ 100,000 IQD</option>
                    <option value="large" {{ request('range') == 'large' ? 'selected' : '' }}>زیاتر لە 100,000 IQD</option>
                </select>

                <button type="submit" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-2xl text-xs transition shadow-md shadow-amber-600/20">
                    گەڕان
                </button>

                @if(request()->hasAny(['search', 'range']))
                    <a href="{{ route('debts.index') }}" class="px-3 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-2xl text-xs border border-slate-700 transition">
                        پاککردنەوە
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- خشتەی یاریزانانی قەرزدار -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl shadow-xl overflow-hidden backdrop-blur-md">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-black text-white">لیستی ئەندامانی قەرزدار</span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-800 text-amber-400 border border-slate-700">
                    {{ $debtors->total() }} ئەندام
                </span>
            </div>
            <span class="text-xs text-slate-500">ڕیزکراوە بەپێی زۆرترین بڕی قەرز</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-400 text-xs border-b border-slate-800">
                        <th class="py-3.5 px-4 font-bold">یاریزان</th>
                        <th class="py-3.5 px-4 font-bold">ژمارەی مۆبایل</th>
                        <th class="py-3.5 px-4 font-bold">سنووری ڕێگەپێدراو</th>
                        <th class="py-3.5 px-4 font-bold text-left">کۆی قەرز (IQD)</th>
                        <th class="py-3.5 px-4 font-bold">دۆخی بەشداری</th>
                        <th class="py-3.5 px-4 font-bold text-center">کردار و دانەوەی قەرز</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    @forelse($debtors as $deb)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <!-- یاریزان -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center font-black text-xs">
                                        {{ mb_substr($deb->full_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-white text-xs">{{ $deb->full_name }}</div>
                                        <div class="text-[10px] font-mono text-slate-400">#{{ $deb->member_code }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- مۆبایل -->
                            <td class="py-3.5 px-4 font-mono text-slate-300">
                                📞 {{ $deb->phone }}
                            </td>

                            <!-- سنووری قەرز -->
                            <td class="py-3.5 px-4">
                                <span class="font-mono text-slate-400 text-[11px]">
                                    {{ $deb->allow_credit ? number_format($deb->credit_limit) . ' IQD' : 'دیارینەکراو' }}
                                </span>
                            </td>

                            <!-- بڕی قەرز -->
                            <td class="py-3.5 px-4 text-left">
                                <span class="font-mono font-black text-sm text-rose-400 bg-rose-500/10 px-2.5 py-1 rounded-xl border border-rose-500/20 inline-block">
                                    {{ number_format($deb->current_debt) }} IQD
                                </span>
                            </td>

                            <!-- دۆخی بەشداری -->
                            <td class="py-3.5 px-4">
                                @if($deb->status === 'active')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        چالاک
                                    </span>
                                @elseif($deb->status === 'frozen')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                        ڕاگیراو
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        بەسەرچوو
                                    </span>
                                @endif
                            </td>

                            <!-- کردارەکان -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <!-- دانەوەی قەرز (Modal) -->
                                    <button type="button"
                                        onclick="openPayModal({{ $deb->id }}, '{{ addslashes($deb->full_name) }}', '{{ $deb->member_code }}', {{ $deb->current_debt }})"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all hover:scale-105 active:scale-95">
                                        <span>💵</span>
                                        <span>دانەوەی قەرز</span>
                                    </button>

                                    <!-- کەشف حیساب -->
                                    <a href="{{ route('debts.statement', $deb->id) }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs border border-slate-700 transition"
                                        title="کەشف حیسابی تەواو">
                                        <span>📜</span>
                                        <span class="hidden sm:inline">کەشف حیساب</span>
                                    </a>

                                    <!-- وەبیرهێنانەوەی واتسئاپ -->
                                    <a href="{{ route('debts.whatsapp.reminder', $deb->id) }}" target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-600 text-emerald-300 hover:text-white font-bold text-xs border border-emerald-500/30 transition shadow-sm"
                                        title="ناردنی نامەی وەبیرهێنانەوە بە واتسئاپ">
                                        <span>📲</span>
                                        <span>WhatsApp</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">🎉</div>
                                <div class="font-bold text-slate-300 text-sm">هیچ قەرزێک لەسەر هیچ یاریزانێک تۆمار نەکراوە!</div>
                                <div class="text-xs text-slate-600 mt-1">سەرجەم حسابات و فرۆشتنەکان پاکتاوکراون</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($debtors->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-800/30">
                {{ $debtors->links() }}
            </div>
        @endif
    </div>

</div>

<!-- ================= Modal: دانەوەی قەرز (Debt Repayment Modal) ================= -->
<div id="pay-debt-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-xl">💵</span>
                <h3 class="text-base font-black text-white">تۆمارکردن و دانەوەی قەرز</h3>
            </div>
            <button type="button" onclick="closePayModal()" class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm">
                ✕
            </button>
        </div>

        <form id="pay-debt-form" method="POST" action="" class="space-y-4">
            @csrf

            <!-- زانیاری یاریزان -->
            <div class="p-3 rounded-2xl bg-slate-800/60 border border-slate-700/60 flex items-center justify-between text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">یاریزانی قەرزدار:</span>
                    <strong id="modal-member-name" class="text-white text-sm"></strong>
                    <span id="modal-member-code" class="text-[10px] text-slate-400 font-mono block"></span>
                </div>
                <div class="text-left">
                    <span class="text-slate-400 block text-[11px]">کۆی قەرزی هەنووکەیی:</span>
                    <span id="modal-total-debt" class="font-mono font-black text-rose-400 text-sm"></span>
                </div>
            </div>

            <!-- بڕی پارەی دانراو -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5 flex items-center justify-between">
                    <span>بڕی پارەی دراو (IQD) *</span>
                    <span class="text-[10px] text-emerald-400">دەتوانیت قیست یان تەواو بدەیت</span>
                </label>
                <input type="number" name="amount" id="modal-amount-input" step="any" min="1" required
                    oninput="updateRemainingDebtPreview()"
                    placeholder="بۆ نموونە: 25000"
                    class="w-full bg-slate-800 border-2 border-slate-700 focus:border-emerald-500 rounded-2xl px-4 py-3 text-white font-mono font-black text-base focus:outline-none transition">

                <!-- دوگمەکانی پڕکردنەوەی خێرا -->
                <div class="flex items-center gap-2 mt-2">
                    <button type="button" onclick="setQuickAmount(1.0)" class="flex-1 py-1 px-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-400 text-[11px] font-bold border border-slate-700 transition">
                        هەموو قەرزەکە (100%)
                    </button>
                    <button type="button" onclick="setQuickAmount(0.5)" class="flex-1 py-1 px-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 text-[11px] font-bold border border-slate-700 transition">
                        نیوەی قەرزەکە (50%)
                    </button>
                </div>
            </div>

            <!-- پێشبینینی قەرزی ماوە پاش دانەوە -->
            <div class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-between text-xs font-bold text-emerald-300">
                <span>قەرزی ماوە پاش ئەم دانەوەیە:</span>
                <span id="modal-remaining-preview" class="font-mono text-sm">0 IQD</span>
            </div>

            <!-- شێوازی پارەدان -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">شێوازی پارەدان *</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="flex items-center justify-center gap-1.5 p-2 rounded-xl border border-slate-700 bg-slate-800 cursor-pointer text-xs font-bold text-white hover:border-emerald-500 transition">
                        <input type="radio" name="payment_method" value="cash" checked class="accent-emerald-500">
                        <span>💵 نەقد</span>
                    </label>
                    <label class="flex items-center justify-center gap-1.5 p-2 rounded-xl border border-slate-700 bg-slate-800 cursor-pointer text-xs font-bold text-white hover:border-emerald-500 transition">
                        <input type="radio" name="payment_method" value="card" class="accent-emerald-500">
                        <span>💳 کارت</span>
                    </label>
                    <label class="flex items-center justify-center gap-1.5 p-2 rounded-xl border border-slate-700 bg-slate-800 cursor-pointer text-xs font-bold text-white hover:border-emerald-500 transition">
                        <input type="radio" name="payment_method" value="bank_transfer" class="accent-emerald-500">
                        <span>🏦 بانک</span>
                    </label>
                </div>
            </div>

            <!-- تێبینی -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">تێبینی (ئارەزوومەندانە)</label>
                <input type="text" name="notes" placeholder="بۆ نموونە: قیستی یەکەم، دراوە بە دەستی کاشێر..."
                    class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500">
            </div>

            <!-- دوگمەکان -->
            <div class="flex items-center gap-2 pt-2">
                <button type="submit" class="flex-1 py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs sm:text-sm shadow-xl shadow-emerald-600/20 transition">
                    ✓ وەرگرتنی پارە و پاکتاوکردن
                </button>
                <button type="button" onclick="closePayModal()" class="py-3 px-4 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition">
                    پەشیمانبوونەوە
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let activeDebtAmount = 0;

    function openPayModal(memberId, name, code, debt) {
        activeDebtAmount = parseFloat(debt);
        document.getElementById('modal-member-name').textContent = name;
        document.getElementById('modal-member-code').textContent = '#' + code;
        document.getElementById('modal-total-debt').textContent = activeDebtAmount.toLocaleString() + ' IQD';

        const form = document.getElementById('pay-debt-form');
        form.action = `/debts/${memberId}/pay`;

        const amountInput = document.getElementById('modal-amount-input');
        amountInput.max = activeDebtAmount;
        amountInput.value = activeDebtAmount; // Default to full amount
        updateRemainingDebtPreview();

        document.getElementById('pay-debt-modal').classList.remove('hidden');
    }

    function closePayModal() {
        document.getElementById('pay-debt-modal').classList.add('hidden');
    }

    function setQuickAmount(factor) {
        const input = document.getElementById('modal-amount-input');
        const val = Math.round(activeDebtAmount * factor);
        input.value = val;
        updateRemainingDebtPreview();
    }

    function updateRemainingDebtPreview() {
        const input = document.getElementById('modal-amount-input');
        const paid = parseFloat(input.value) || 0;
        const remaining = Math.max(0, activeDebtAmount - paid);
        const preview = document.getElementById('modal-remaining-preview');
        preview.textContent = remaining.toLocaleString() + ' IQD';
    }
</script>
@endpush
@endsection
