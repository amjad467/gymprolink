@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Gym Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-850 to-slate-900 border border-slate-800 rounded-2xl p-6 relative overflow-hidden shadow-xl">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-10">
            <div>
                <span class="inline-block px-2.5 py-1 rounded-md text-xs font-bold uppercase bg-emerald-500/20 text-emerald-400 mb-2">
                    {{ $activeGym->code }}
                </span>
                <h1 class="text-2xl font-black text-white flex items-center gap-2">
                    🏛️ {{ $activeGym->name }}
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    {{ $activeGym->address }} | کاتی کارکردن: {{ $activeGym->opening_hours }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('gate.simulator') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-emerald-600/30 transition flex items-center gap-2">
                    <span>🚪 سکەنەری دەرگا</span>
                </a>
                <a href="{{ route('pos.index') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                    <span>🛍️ خاڵی فرۆشتن (POS)</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. ئەندامانی چالاک -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold text-slate-400 block mb-1">ئەندامانی چالاک</span>
                    <span class="text-3xl font-black text-white">{{ $activeMembers }}</span>
                    <span class="text-xs text-slate-500 block mt-1">کۆی گشتی: {{ $totalMembers }} ئەندام</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl">
                    👥
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 text-xs">
                <span class="text-rose-400 font-bold">{{ $expiredMembers }} بەسەرچوو</span>
                <span class="text-slate-600">•</span>
                <span class="text-amber-400 font-bold">{{ $frozenMembers }} ڕاگیراو</span>
            </div>
        </div>

        <!-- 2. سەردانی ئەمڕۆی دەرگا -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold text-slate-400 block mb-1">سەردانی دەرگای ئەمڕۆ</span>
                    <span class="text-3xl font-black text-emerald-400">{{ $todayGrantedCount }}</span>
                    <span class="text-xs text-rose-400 font-semibold block mt-1">{{ $todayDeniedCount }} ڕەتکراوەتەوە</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-teal-500/10 border border-teal-500/20 text-teal-400 flex items-center justify-center text-xl">
                    🚪
                </div>
            </div>
            <div class="mt-4 text-xs text-slate-400">
                کۆی هەوڵەکانی سکەن: <span class="font-bold text-white">{{ $todayScansCount }}</span>
            </div>
        </div>

        <!-- 3. داهاتی بەشداریی هۆڵ -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold text-slate-400 block mb-1">داهاتی بەشداری (مانگانە)</span>
                    <span class="text-2xl font-black text-white">{{ number_format($membershipRevenueMonth) }} <span class="text-xs font-normal text-slate-400">IQD</span></span>
                    <span class="text-xs text-emerald-400 block mt-1">تەنها بەشداریی هۆڵ</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-xl">
                    💳
                </div>
            </div>
            <div class="mt-4 text-xs text-slate-500">
                حسابی سەربەخۆ لە شۆپ
            </div>
        </div>

        <!-- 4. قازانجی شۆپی تەواوکەر -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold text-slate-400 block mb-1">قازانجی خاوێنی شۆپ (مانگانە)</span>
                    <span class="text-2xl font-black text-emerald-400">{{ number_format($shopGrossProfitMonth) }} <span class="text-xs font-normal text-slate-400">IQD</span></span>
                    <span class="text-xs text-slate-400 block mt-1">فرۆشتن: {{ number_format($shopRevenueMonth) }} IQD</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-xl">
                    🛍️
                </div>
            </div>
            <div class="mt-4 text-xs text-slate-500">
                هەژمارکراو بەپێی FEFO COGS
            </div>
        </div>
    </div>

    <!-- Tables Grid: Recent Gate Logs & Inventory Alert -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- لاگی ڕاستەوخۆی سکەنەکانی دەرگا (2 cols) -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <span>⚡ کۆتا چوونەژوورەوەکانی دەرگای زیرەک</span>
                </h2>
                <a href="{{ route('gate.simulator') }}" class="text-xs text-emerald-400 hover:text-emerald-300 font-semibold">
                    بینینی سکەنەر &larr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="text-slate-500 border-b border-slate-800">
                            <th class="pb-3 font-semibold">ئەندام</th>
                            <th class="pb-3 font-semibold">دۆخی چوونەژوورەوە</th>
                            <th class="pb-3 font-semibold">هۆکار / پەیام</th>
                            <th class="pb-3 font-semibold">کات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($recentAccessEvents as $ev)
                            <tr class="hover:bg-slate-850/40 transition">
                                <td class="py-3 font-medium text-white">
                                    {{ $ev->member?->full_name ?? 'نەناسراو' }}
                                    @if($ev->member)
                                        <span class="text-[10px] text-slate-500 block font-mono">{{ $ev->member->member_code }}</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    @if($ev->status === 'GRANTED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            ✓ ڕێگەپێدراوە
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                            ✕ ڕەتکرایەوە
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 text-slate-300">
                                    {{ $ev->denial_reason ?? 'بەخێربێیت' }}
                                </td>
                                <td class="py-3 text-slate-500 font-mono text-[11px]">
                                    {{ $ev->scanned_at ? $ev->scanned_at->format('H:i:s') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-500">
                                    هیچ ڕووداوێکی سکەنکردن لەم هۆڵە تۆمار نەکراوە.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ئاگاداری کاڵاکانی شۆپ و بەرواری بەسەرچوون (1 col) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <span>⚠️ ئاگاداری کۆگا و بەسەرچوون</span>
                </h2>
                <a href="{{ route('pos.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold">
                    شۆپ &larr;
                </a>
            </div>

            <div class="space-y-3">
                @forelse($expiringBatches as $batch)
                    <div class="p-3 rounded-xl bg-slate-800/60 border border-slate-700/60 text-xs">
                        <div class="flex justify-between items-start">
                            <span class="font-bold text-slate-200">{{ $batch->variant?->product?->name_ku }}</span>
                            <span class="text-amber-400 font-bold bg-amber-500/10 px-2 py-0.5 rounded">
                                {{ $batch->quantity_available }} دانە
                            </span>
                        </div>
                        <div class="text-slate-400 text-[11px] mt-1">
                            تام: {{ $batch->variant?->flavor ?? '-' }} | قەبارە: {{ $batch->variant?->size ?? '-' }}
                        </div>
                        <div class="flex justify-between items-center mt-2 text-[11px] text-slate-500 border-t border-slate-700/40 pt-1">
                            <span>وەجبە: <span class="font-mono text-slate-300">{{ $batch->batch_number }}</span></span>
                            <span class="text-rose-400 font-semibold">بەسەرچوون: {{ $batch->expiry_date->format('Y-m-d') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-500 text-xs">
                        هیچ کاڵایەکی نزیک لە بەسەرچوون یان کەمبوونەوە نییە.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
