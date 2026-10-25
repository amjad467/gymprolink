@extends('layouts.app')

@section('title', 'بەڕێوەبردنی خەرجییەکانی هۆڵ - ' . ($activeGym->name ?? 'GymPro'))

@section('content')
<div class="space-y-6">

    <!-- سەرپەڕی خەرجییەکان -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-slate-900/90 border border-slate-800 p-6 rounded-3xl shadow-xl backdrop-blur-md">
        <div class="flex items-center gap-3">
            <span class="p-3 bg-rose-500/10 text-rose-400 rounded-2xl text-2xl border border-rose-500/20">
                💸
            </span>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">بەڕێوەبردنی خەرجییە گشتییەکانی هۆڵ</h1>
                <p class="text-xs text-slate-400 mt-1">
                    تۆمارکردنی خەرجییەکانی مۆلیدە، کرێ، پاکوخاوێنی، مووچە و پێداویستییەکان بۆ هەژمارکردنی قازانجی ڕاستەقینە
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <a href="{{ route('financial.closing') }}" class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-emerald-600/30 transition-all hover:scale-[1.02] active:scale-[0.98]">
                <span>📊</span>
                <span>داخستنی دارایی ڕۆژانە (Z-Report)</span>
            </a>
            <a href="{{ route('analytics.sales') }}" class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-4 py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs sm:text-sm transition">
                <span>📈</span>
                <span>شیکاری دارایی</span>
            </a>
        </div>
    </div>

    <!-- کارتەکانی ئاماری خەرجییەکان (KPIs) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- خەرجی ئەمڕۆ -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex items-center justify-between shadow-md">
            <div>
                <span class="text-xs font-bold text-slate-400 block mb-1">خەرجییەکانی ئەمڕۆ</span>
                <span class="text-2xl font-black text-rose-400 font-mono">{{ number_format($todayExpenses) }}</span>
                <span class="text-[10px] text-slate-500 block mt-1">دیناری خەرجکراوی ئەمڕۆ</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-xl border border-rose-500/20">
                ⚡
            </div>
        </div>

        <!-- خەرجی ئەم مانگە -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex items-center justify-between shadow-md">
            <div>
                <span class="text-xs font-bold text-slate-400 block mb-1">خەرجییەکانی ئەم مانگە</span>
                <span class="text-2xl font-black text-amber-400 font-mono">{{ number_format($thisMonthExpenses) }}</span>
                <span class="text-[10px] text-slate-500 block mt-1">لە سەرەتای ئەم مانگەوە</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl border border-amber-500/20">
                📅
            </div>
        </div>

        <!-- کۆی هەموو خەرجییەکان -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex items-center justify-between shadow-md">
            <div>
                <span class="text-xs font-bold text-slate-400 block mb-1">کۆی گشتی خەرجییەکان</span>
                <span class="text-2xl font-black text-purple-400 font-mono">{{ number_format($totalExpensesAllTime) }}</span>
                <span class="text-[10px] text-slate-500 block mt-1">لە دەستپێکی تۆمارکردنەوە</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-xl border border-purple-500/20">
                💰
            </div>
        </div>

        <!-- ژمارەی وەسڵەکان -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex items-center justify-between shadow-md">
            <div>
                <span class="text-xs font-bold text-slate-400 block mb-1">ژمارەی خەرجییەکان</span>
                <span class="text-2xl font-black text-white font-mono">{{ number_format($totalReceiptsCount) }}</span>
                <span class="text-[10px] text-slate-500 block mt-1">تۆماری فەرمی</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-xl border border-blue-500/20">
                🧾
            </div>
        </div>
    </div>

    <!-- Layout: فۆرمی زیادکردن لە لایەک، خشتەی خەرجییەکان لە لایەکی تر -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- فۆرمی زیادکردنی خەرجی نوێ (4 cols) -->
        <div class="lg:col-span-4">
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-md sticky top-24">
                <div class="flex items-center gap-2 border-b border-slate-800 pb-3 mb-4">
                    <span class="text-lg">➕</span>
                    <h2 class="text-base font-black text-white">تۆمارکردنی خەرجی نوێ</h2>
                </div>

                <form action="{{ route('expenses.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">ناونیشانی خەرجی *</label>
                        <input type="text" name="title" required placeholder="بۆ نموونە: پارەی مۆلیدەی هۆڵ بۆ ئەم مانگە"
                            class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 transition">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">بەشی خەرجی *</label>
                            <select name="category" required class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                                @foreach($categories as $key => $catName)
                                    <option value="{{ $key }}">{{ $catName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">بڕی پارە (IQD) *</label>
                            <input type="number" name="amount" required min="1" step="0.01" placeholder="بۆ نموونە: 150000"
                                class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white font-mono font-bold focus:outline-none focus:border-rose-500 transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">بەرواری خەرجکردن *</label>
                            <input type="date" name="spent_at" required value="{{ date('Y-m-d') }}"
                                class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">بە کێ دراوە؟ (Paid To)</label>
                            <input type="text" name="paid_to" placeholder="وەستای مۆلیدە، فرۆشیار..."
                                class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">تێبینی یان هۆکار (ئارەزوومەندانە)</label>
                        <textarea name="notes" rows="2" placeholder="تێبینی زیادە لەسەر ئەم خەرجییە..."
                            class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 transition"></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-black text-xs shadow-lg shadow-rose-600/30 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                        <span>💸</span>
                        <span>تۆمارکردنی خەرجی</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- خشتەی هەموو خەرجییەکان (8 cols) -->
        <div class="lg:col-span-8 space-y-4">
            <!-- فۆرمی فلتەر و گەڕان -->
            <div class="bg-slate-900/80 border border-slate-800 p-4 rounded-3xl shadow-lg">
                <form method="GET" action="{{ route('expenses.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="گەڕان بە ناونیشان، بە کێ دراوە..."
                            class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 transition">
                    </div>

                    <div>
                        <select name="category" class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                            <option value="ALL">هەموو بەشەکان</option>
                            @foreach($categories as $key => $catName)
                                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $catName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <select name="date_filter" class="flex-1 bg-slate-800/90 border border-slate-700 rounded-2xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                            <option value="all" {{ request('date_filter') == 'all' ? 'selected' : '' }}>هەموو کاتەکان</option>
                            <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>ئەمڕۆ</option>
                            <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>ئەم هەفتەیە</option>
                            <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>ئەم مانگە</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-2xl text-xs border border-slate-700 transition">
                            فلتەر
                        </button>
                    </div>
                </form>
            </div>

            <!-- خشتەی خەرجییەکان -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl shadow-xl overflow-hidden backdrop-blur-md">
                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse">
                        <thead>
                            <tr class="bg-slate-800/50 text-slate-400 text-xs border-b border-slate-800">
                                <th class="py-3.5 px-4 font-bold">بەشی خەرجی</th>
                                <th class="py-3.5 px-4 font-bold">ناونیشان و ڕوونکردنەوە</th>
                                <th class="py-3.5 px-4 font-bold">بەروار</th>
                                <th class="py-3.5 px-4 font-bold">بە کێ دراوە</th>
                                <th class="py-3.5 px-4 font-bold text-left">بڕی خەرجی (IQD)</th>
                                <th class="py-3.5 px-4 font-bold text-center">کردار</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-xs">
                            @forelse($expenses as $exp)
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-xl text-[10.5px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                            {{ $categories[$exp->category] ?? $exp->category }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-white">{{ $exp->title }}</div>
                                        @if($exp->notes)
                                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $exp->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-300 font-mono text-[11px]">
                                        {{ $exp->spent_at ? $exp->spent_at->format('Y-m-d') : 'ـ' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-400 font-bold">
                                        {{ $exp->paid_to ?? 'دیارینەکراو' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-left font-mono font-black text-rose-400 text-sm">
                                        {{ number_format($exp->amount) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <form action="{{ route('expenses.destroy', $exp->id) }}" method="POST" onsubmit="return confirm('دڵنیایت لە سڕینەوەی ئەم خەرجییە؟')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-400 transition" title="سڕینەوە">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-500">
                                        <div class="text-3xl mb-2">💸</div>
                                        <div class="font-bold text-slate-400 text-sm">هیچ خەرجییەک نەدۆزرایەوە!</div>
                                        <div class="text-xs text-slate-600 mt-1">دەتوانیت لە ڕێگەی فۆرمەکەی لای ڕاست خەرجی نوێ تۆمار بکەیت</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($expenses->hasPages())
                    <div class="p-4 border-t border-slate-800 bg-slate-800/30">
                        {{ $expenses->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
