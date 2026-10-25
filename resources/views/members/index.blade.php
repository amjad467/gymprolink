@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto px-2 sm:px-4">

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border-2 border-emerald-500/40 text-emerald-300 font-bold text-sm flex items-center justify-between shadow-xl animate-pulse">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border-2 border-rose-500/40 text-rose-300 font-bold text-sm flex items-center justify-between shadow-xl">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white">&times;</button>
        </div>
    @endif

    <!-- Header & Register Action Button -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 shadow-2xl relative overflow-hidden">
        <div class="absolute -top-24 -left-24 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative">
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    بەڕێوەبردنی ئەندامێتی و بەشدارییەکان
                </span>
                <span class="text-xs text-slate-400 font-mono hidden sm:inline">•</span>
                <span class="text-xs text-slate-400 font-bold hidden sm:inline">{{ $activeGym->name }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white flex items-center gap-2">
                <span>👥 تۆماری یاریزانان و پلانەکانی بەشداری</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                ناونووسینی یاریزانی نوێ بە بەرواری دەستپێکردن و کۆتایی ڕوون، نوێکردنەوە، و ڕاگرتن/دەستپێکردنەوەی بەشداری
            </p>
        </div>

        <div class="relative flex flex-wrap items-center gap-3">
            <button type="button" onclick="openExpiredArchiveModal()" class="relative bg-gradient-to-r from-amber-600 via-rose-600 to-amber-700 hover:from-amber-500 hover:to-rose-500 text-white font-black text-xs sm:text-sm px-4 py-3 rounded-2xl shadow-lg shadow-rose-600/20 transition flex items-center gap-2 hover:scale-[1.02] active:scale-[0.98]">
                <span class="text-base">🗄️</span>
                <span>ئەرشیفی بەسەرچووەکان (15+ / 30+ / 60+ ڕۆژ)</span>
                @if($totalDormantCount > 0)
                    <span class="bg-white text-rose-700 text-xs px-2 py-0.5 rounded-full font-black animate-pulse">
                        {{ $totalDormantCount }}
                    </span>
                @endif
            </button>
            <button type="button" onclick="openRegisterModal()" class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-xs sm:text-sm px-5 py-3 rounded-2xl shadow-lg shadow-emerald-600/30 transition flex items-center gap-2 hover:scale-[1.02] active:scale-[0.98]">
                <span class="text-base">➕</span>
                <span>تۆمارکردنی یاریزانی نوێ (ناونووسین)</span>
            </button>
        </div>
    </div>

    <!-- Search & Filter Form -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-xl">
        <form action="{{ route('members.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-6">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="گەڕان بەپێی ناوی یاریزان، ژمارەی مۆبایل، یان کۆدی بارکۆد..." class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl pr-10 pl-3 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition">
                    <span class="absolute right-3.5 top-3 text-slate-400">🔍</span>
                </div>
            </div>
            <div class="sm:col-span-3">
                <select name="status" class="w-full bg-slate-800/90 border border-slate-700 rounded-2xl px-3 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 font-bold">
                    <option value="">-- سەرجەم دۆخەکان --</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>🟢 چالاکەکان (Active)</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>🔴 بەسەرچووەکان (Expired)</option>
                    <option value="frozen" {{ request('status') === 'frozen' ? 'selected' : '' }}>❄️ ڕاگیراوەکان (Frozen)</option>
                </select>
            </div>
            <div class="sm:col-span-3 flex gap-2">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black px-4 py-2.5 rounded-2xl text-xs transition shadow-lg shadow-emerald-600/20">
                    فلتەرکردن
                </button>
                <a href="{{ route('members.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2.5 rounded-2xl text-xs font-bold transition flex items-center justify-center">
                    پاککردنەوە
                </a>
            </div>
        </form>
    <!-- Expiring Alerts Toolbar (ئاگادارکردنەوەی بەسەرچوونی بەشداری پێش ٧، ٣ و ١ ڕۆژ) -->
    <div class="flex flex-wrap items-center gap-2 bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-xl">
        <span class="text-xs font-bold text-slate-400 ml-2">⏳ فلتەری ئاگاداری بەسەرچوون:</span>

        <a href="{{ route('members.index', ['filter' => 'expiring_7d']) }}" class="px-3.5 py-2 rounded-2xl text-xs font-bold transition flex items-center gap-2 {{ request('filter') === 'expiring_7d' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-800 text-indigo-300 hover:bg-slate-750' }}">
            <span>📅</span>
            <span>بەسەرچوون لە ٧ ڕۆژدا</span>
            <span class="bg-indigo-500/30 text-indigo-200 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $expiring7dCount }}</span>
        </a>

        <a href="{{ route('members.index', ['filter' => 'expiring_3d']) }}" class="px-3.5 py-2 rounded-2xl text-xs font-bold transition flex items-center gap-2 {{ request('filter') === 'expiring_3d' ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'bg-slate-800 text-amber-300 hover:bg-slate-750' }}">
            <span>⚠️</span>
            <span>بەسەرچوون لە ٣ ڕۆژدا</span>
            <span class="bg-amber-500/30 text-amber-200 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $expiring3dCount }}</span>
        </a>

        <a href="{{ route('members.index', ['filter' => 'expiring_1d']) }}" class="px-3.5 py-2 rounded-2xl text-xs font-bold transition flex items-center gap-2 {{ request('filter') === 'expiring_1d' ? 'bg-rose-600 text-white shadow-lg shadow-rose-600/30' : 'bg-slate-800 text-rose-300 hover:bg-slate-750' }}">
            <span>🚨</span>
            <span>بەسەرچوون سبەی (١ ڕۆژ)</span>
            <span class="bg-rose-500/30 text-rose-200 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $expiring1dCount }}</span>
        </a>

        @if(request()->filled('filter'))
            <a href="{{ route('members.index') }}" class="text-xs text-slate-400 hover:text-white mr-auto transition">
                ✕ هەڵوەشاندنەوەی فلتەر
            </a>
        @endif
    </div>

    <!-- Members Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-950/80 text-slate-400 border-b border-slate-800 uppercase text-[11px] font-black">
                        <th class="p-4">یاریزان</th>
                        <th class="p-4 font-mono">بارکۆد / ناسنامە</th>
                        <th class="p-4">پلانی بەشداری</th>
                        <th class="p-4">بەرواری سەرەتا و کۆتایی</th>
                        <th class="p-4">دۆخی بەشداری</th>
                        <th class="p-4">باڵانسی قەرز</th>
                        <th class="p-4 text-center">کردارەکان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($members as $m)
                        @php
                            $activeMembership = $m->activeMembership;
                            $frozenMembership = $m->memberships()->where('status', 'frozen')->latest()->first();
                            $membership = $activeMembership ?? $frozenMembership ?? $m->memberships->first();
                            $plan = $membership?->plan;
                            $latestFreeze = $frozenMembership?->freezes?->where('status', 'active')->first() ?? $membership?->freezes?->first();

                            $statusBadge = match($m->status) {
                                'active' => ['bg' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30', 'label' => '🟢 چالاک'],
                                'expired' => ['bg' => 'bg-rose-500/20 text-rose-300 border-rose-500/30', 'label' => '🔴 بەسەرچوو'],
                                'frozen' => ['bg' => 'bg-amber-500/20 text-amber-300 border-amber-500/30', 'label' => '❄️ ڕاگیراو'],
                                default => ['bg' => 'bg-slate-500/20 text-slate-300 border-slate-500/30', 'label' => $m->status]
                            };
                            $cred = $m->primaryCredential;
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <!-- Player Info -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-sm text-emerald-400 overflow-hidden shadow-inner flex-shrink-0">
                                        @if($m->photo_url)
                                            <img src="{{ $m->photo_url }}" alt="{{ $m->full_name }}" class="w-full h-full object-cover">
                                        @else
                                            {{ mb_substr($m->full_name, 0, 1) }}
                                        @endif
                                    </div>
                                    <div>
                                        <span class="font-bold text-white text-sm block">{{ $m->full_name }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono">{{ $m->phone }} • {{ $m->gender === 'male' ? 'نێر' : 'مێ' }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Member Code / Barcode -->
                            <td class="p-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl font-mono text-xs font-bold bg-slate-950 text-emerald-400 border border-slate-800">
                                    <span>🏷️</span>
                                    <span>{{ $m->member_code }}</span>
                                </span>
                            </td>

                            <!-- Plan Type -->
                            <td class="p-4">
                                <div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                        {{ $plan?->name ?? 'بێ پلان' }}
                                    </span>
                                    @if($plan)
                                        <span class="text-[10px] text-slate-400 block mt-0.5">
                                            {{ number_format($plan->price) }} IQD • {{ $plan->duration_days }} ڕۆژ
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Start & End Date (بەرواری سەرەتا و کۆتایی) -->
                            <td class="p-4 font-mono text-xs">
                                @if($membership)
                                    <div class="space-y-0.5">
                                        <span class="text-slate-300 block">
                                            دەستپێک: <strong class="text-white">{{ $membership->start_date ? $membership->start_date->format('Y-m-d') : 'ـ' }}</strong>
                                        </span>
                                        <span class="block {{ $m->status === 'expired' ? 'text-rose-400' : 'text-emerald-400' }} font-bold">
                                            کۆتایی: {{ $membership->end_date ? $membership->end_date->format('Y-m-d') : 'دیارینەکراو' }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-500 font-sans">هیچ بەشدارییەک نییە</span>
                                @endif
                            </td>

                            <!-- Status & Freeze Details -->
                            <td class="p-4">
                                <div class="space-y-1">
                                    <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full text-xs font-black border {{ $statusBadge['bg'] }}">
                                        {{ $statusBadge['label'] }}
                                    </span>

                                    <!-- ئەگەر ئەندامەکە ڕاگیرابێت: زانیاری وردی کاتی ڕاگرتن و هۆکار -->
                                    @if($m->status === 'frozen' && $latestFreeze)
                                        <div class="mt-1 p-2 rounded-xl bg-amber-500/10 border border-amber-500/30 text-[10px] text-amber-300 max-w-xs space-y-0.5">
                                            <span class="block font-bold">
                                                ❄️ ڕاگیراوە لە: {{ $latestFreeze->freeze_start_date ? \Carbon\Carbon::parse($latestFreeze->freeze_start_date)->format('Y-m-d') : 'ـ' }}
                                            </span>
                                            <span class="block text-slate-300">
                                                ماوەی ڕاگرتن: <strong>{{ $latestFreeze->days_count }} ڕۆژ</strong>
                                            </span>
                                            <span class="block text-slate-400 truncate" title="{{ $latestFreeze->reason }}">
                                                هۆکار: {{ $latestFreeze->reason }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Debt -->
                            <td class="p-4 font-mono font-bold text-xs {{ $m->current_debt > 0 ? 'text-rose-400' : 'text-slate-400' }}">
                                {{ number_format($m->current_debt) }} IQD
                            </td>

                            <!-- Actions -->
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <!-- پرۆفایل و مێژووی ئەندام (Profile & History) -->
                                    <a href="{{ route('members.show', $m->id) }}" 
                                       class="bg-slate-800 hover:bg-slate-700 text-indigo-300 hover:text-white px-2.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 border border-slate-700" 
                                       title="بینینی پرۆفایل، مێژووی بەشداری و سەردانەکان">
                                        <span>👤</span>
                                        <span>پرۆفایل</span>
                                    </a>

                                    <!-- کارتی ئەندام (ID Card) -->
                                    <a href="{{ route('members.card', $m->id) }}" target="_blank"
                                       class="bg-purple-600/30 hover:bg-purple-600 text-purple-300 hover:text-white border border-purple-500/30 px-2.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1"
                                       title="چاپکردنی کارتی ئەندام بە QR و ناسنامە">
                                        <span>🪪</span>
                                        <span>کارت</span>
                                    </a>

                                    <!-- نوێکردنەوەی بەشداری (Renew) -->
                                    <button type="button" 
                                            onclick="openRenewModal({{ $m->id }}, '{{ addslashes($m->full_name) }}', '{{ $plan?->id }}', '{{ $membership?->end_date ? $membership->end_date->format('Y-m-d') : '' }}')" 
                                            class="bg-indigo-600/80 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow">
                                        <span>🔄 نوێکردنەوە</span>
                                    </button>

                                    <!-- ئەگەر چالاک بێت: ڕاگرتن (Freeze) -->
                                    @if($m->status === 'active' && $activeMembership)
                                        <button type="button" 
                                                onclick="openFreezeModal({{ $m->id }}, '{{ addslashes($m->full_name) }}', '{{ $activeMembership->end_date ? $activeMembership->end_date->format('Y-m-d') : '' }}')" 
                                                class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1">
                                            <span>❄️ ڕاگرتن</span>
                                        </button>
                                    @elseif($m->status === 'frozen')
                                        <!-- ئەگەر ڕاگیرابێت: دەستپێکردنەوە (Unfreeze) لەگەڵ پیشاندانی بەرواری درێژکراوە -->
                                        @php
                                            $frozenDays = $latestFreeze ? max(1, \Carbon\Carbon::parse($latestFreeze->freeze_start_date)->diffInDays(now())) : 1;
                                            $daysToAdd = $latestFreeze ? min($latestFreeze->days_count, $frozenDays) : 1;
                                            $projectedNewEnd = $membership?->end_date ? $membership->end_date->copy()->addDays($daysToAdd)->format('Y-m-d') : '';
                                        @endphp
                                        <button type="button" 
                                                onclick="openUnfreezeModal({{ $m->id }}, '{{ addslashes($m->full_name) }}', '{{ $latestFreeze?->freeze_start_date }}', '{{ $projectedNewEnd }}')" 
                                                class="bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow animate-pulse">
                                            <span>▶️ دەستپێکردنەوە</span>
                                        </button>
                                    @endif

                                    <!-- QR Code Button -->
                                    @if($cred)
                                        <button type="button" onclick="showQrModal('{{ addslashes($m->full_name) }}', '{{ $m->member_code }}', '{{ $cred->raw_token }}')" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1.5 rounded-xl text-xs transition" title="بینینی کۆدی QR">
                                            📱 QR
                                        </button>
                                    @endif

                                    <!-- وەسڵی ئەندامێتی بە واتسئاپ -->
                                    <a href="{{ route('members.whatsapp.receipt', $m->id) }}" target="_blank"
                                        class="bg-emerald-500/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 px-2.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-sm"
                                        title="ناردنی وەسڵی ئەندامێتی بە WhatsApp بۆ مۆبایلی یاریزان">
                                        <span>📲</span>
                                        <span>وەسڵ</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">👥</div>
                                <div class="text-sm font-bold text-slate-400">هیچ یاریزانێک بەم زانیارییانە نەدۆزرایەوە.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-800 bg-slate-950/60">
            {{ $members->links() }}
        </div>
    </div>

</div>

<!-- ========================================================================= -->
<!-- Modal 1: فۆرمی تۆمارکردنی یاریزانی نوێ بە بەرواری دیاریکراو و پلانی ڕێکخراو -->
<!-- ========================================================================= -->
<div id="register-modal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-3xl w-full p-6 sm:p-7 space-y-5 max-h-[92vh] overflow-y-auto shadow-2xl relative">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <div>
                <h3 class="text-lg font-black text-white flex items-center gap-2">
                    <span>➕ تۆمارکردنی یاریزانی نوێ لە {{ $activeGym->name }}</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">زانیاری کەسی، هەڵبژاردنی پلانی بەشداری و بەروارەکان بە شێوازی کراوە</p>
            </div>
            <button type="button" onclick="closeRegisterModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center text-lg">&times;</button>
        </div>

        <form action="{{ route('members.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- ١. زانیارییە کەسییەکان -->
            <div class="space-y-3">
                <span class="text-xs font-black text-slate-300 block border-b border-slate-800 pb-1">١. زانیارییە سەرەکییەکانی یاریزان:</span>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">ناوی تەواوی یاریزان *</label>
                        <input type="text" name="full_name" required placeholder="ناوی سیانی بنووسە..." class="w-full bg-slate-800/90 border border-slate-700 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-white text-xs focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">ژمارەی مۆبایل *</label>
                        <input type="text" name="phone" required placeholder="0750xxxxxxx" class="w-full bg-slate-800/90 border border-slate-700 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-white text-xs font-mono text-left focus:outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">ڕەگەز *</label>
                        <select name="gender" class="w-full bg-slate-800/90 border border-slate-700 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-white text-xs focus:outline-none transition">
                            <option value="male">نێر (کوڕ)</option>
                            <option value="female">مێ (کچ)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">بەرواری لەدایکبوون</label>
                        <input type="date" name="birth_date" class="w-full bg-slate-800/90 border border-slate-700 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-white text-xs font-mono focus:outline-none transition">
                    </div>
                </div>
            </div>

            <!-- ٢. پلانی بەشداری بەشێوازێکی زۆر ڕێکخراو و ڕوون (Structured Plan Selection) -->
            <div class="p-5 bg-slate-950/70 rounded-2xl border border-slate-800 space-y-4">
                <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                    <span class="text-xs font-black text-emerald-400 flex items-center gap-2">
                        <span>🏷️</span>
                        <span>٢. هەڵبژاردنی پلانی بەشداری (Membership Plan):</span>
                    </span>
                    <span class="text-[11px] text-slate-400">کلیک لەسەر هەر پلانێک بکە بۆ هەڵبژاردن</span>
                </div>

                <!-- Plan Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($plans as $p)
                        <div onclick="selectRegPlan({{ $p->id }}, {{ $p->price }}, {{ $p->duration_days }}, '{{ addslashes($p->name) }}')" 
                             id="reg-plan-card-{{ $p->id }}" 
                             class="reg-plan-card cursor-pointer p-3.5 rounded-2xl border border-slate-700 bg-slate-900 hover:border-emerald-500/60 transition relative overflow-hidden group">
                            
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-sm text-white block group-hover:text-emerald-300 transition">
                                    {{ $p->name }}
                                </span>
                                <span class="plan-check-badge hidden w-5 h-5 rounded-full bg-emerald-500 text-slate-950 text-xs font-black flex items-center justify-center">
                                    ✓
                                </span>
                            </div>

                            <div class="text-base font-black text-emerald-400 font-mono">
                                {{ number_format($p->price) }} <span class="text-[10px] text-slate-400 font-sans">IQD</span>
                            </div>

                            <div class="mt-2 pt-2 border-t border-slate-800 text-[11px] text-slate-400 flex justify-between items-center">
                                <span>ماوە: <strong class="text-slate-200">{{ $p->duration_days }} ڕۆژ</strong></span>
                                <span>{{ $p->visits_limit ? $p->visits_limit . ' سەردان' : 'بێ سنوور' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Hidden select synced with cards -->
                <select name="plan_id" id="reg-plan-select" onchange="onRegPlanSelectChanged()" required class="hidden">
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" data-price="{{ $p->price }}" data-days="{{ $p->duration_days }}" data-name="{{ $p->name }}">
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>

                <!-- ٣. بەرواری سەرەتا و کۆتایی (کراوەیە و لۆک نییە بە تەواوی وەک داواکرابوو) -->
                <div class="pt-2 border-t border-slate-800">
                    <span class="text-xs font-black text-slate-300 block mb-2">
                        📅 بەروارەکانی دەستپێکردن و بەسەرچوون (دەتوانیت بە دڵی خۆت دەستکارییان بکەیت و لۆک نین):
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- بەرواری دەستپێکردن -->
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1 flex items-center justify-between">
                                <span>بەرواری دەستپێکردن (Start Date) *</span>
                                <span class="text-[10px] text-emerald-400">کراوەیە بۆ دەستکاری 🔓</span>
                            </label>
                            <input type="date" 
                                   name="start_date" 
                                   id="reg-start-date" 
                                   value="{{ now()->format('Y-m-d') }}" 
                                   onchange="onRegStartDateChanged()" 
                                   required 
                                   class="w-full bg-slate-900 border-2 border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-2 text-white font-mono font-bold text-xs focus:outline-none transition">
                        </div>

                        <!-- بەرواری کۆتایی -->
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1 flex items-center justify-between">
                                <span>بەرواری کۆتایی (End Date) *</span>
                                <span id="reg-duration-tag" class="text-[10px] text-indigo-400 font-bold">ماوە: ٣٠ ڕۆژ</span>
                            </label>
                            <input type="date" 
                                   name="end_date" 
                                   id="reg-end-date" 
                                   required 
                                   class="w-full bg-slate-900 border-2 border-slate-700 focus:border-indigo-500 rounded-xl px-3 py-2 text-white font-mono font-bold text-xs focus:outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- ٤. بڕی پارەدان و داشکاندن -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">داشکاندن (گەر هەبێت)</label>
                        <input type="number" name="discount" id="reg-discount" value="0" step="0.01" oninput="updateRegTotal()" class="w-full bg-slate-900 border border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-emerald-400 mb-1">بڕی دراو بە نەقد/کارت *</label>
                        <input type="number" name="paid_amount" id="reg-paid" step="0.01" required class="w-full bg-slate-900 border-2 border-emerald-500/50 focus:border-emerald-500 rounded-xl px-3 py-2 text-emerald-300 text-xs font-black focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">شێوازی پارەدان *</label>
                        <select name="payment_method" class="w-full bg-slate-900 border border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-2 text-white text-xs focus:outline-none">
                            <option value="cash">💵 نەقد</option>
                            <option value="card">💳 کارت</option>
                            <option value="bank_transfer">🏦 حەواڵەی بانکی</option>
                        </select>
                    </div>
                </div>

            </div>

            <!-- سیاسەتی قەرز و مۆڵەت لە شۆپ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="allow_credit" id="allow_credit" value="1" class="rounded bg-slate-800 border-slate-700 text-emerald-500 focus:ring-emerald-500">
                    <label for="allow_credit" class="text-xs font-bold text-slate-300 cursor-pointer">ڕێگەدان بە کڕین بە قەرز لە شۆپ</label>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">سنووری قەرز (IQD)</label>
                    <input type="number" name="credit_limit" value="50000" step="0.01" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-mono">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeRegisterModal()" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700 transition">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 shadow-lg shadow-emerald-600/30 transition">
                    تۆمارکردن و دروستکردنی کارتی یاریزان ✓
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal 2: نوێکردنەوەی بەشداری (Renew Membership Modal) بە بەرواری کراوە -->
<!-- ========================================================================= -->
<div id="renew-modal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <div>
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <span>🔄 نوێکردنەوەی بەشداریی یاریزان</span>
                </h3>
                <p id="renew-member-name" class="text-xs text-indigo-400 font-bold mt-0.5"></p>
            </div>
            <button type="button" onclick="closeRenewModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center text-lg">&times;</button>
        </div>

        <form id="renew-form" method="POST" action="" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">پلانی بەشداری نوێ *</label>
                <select name="plan_id" id="renew-plan-select" onchange="onRenewPlanChanged()" required class="w-full bg-slate-800 border border-slate-700 focus:border-indigo-500 rounded-xl px-3.5 py-2.5 text-white text-xs font-bold focus:outline-none">
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" data-price="{{ $plan->price }}" data-days="{{ $plan->duration_days }}">
                            {{ $plan->name }} ({{ number_format($plan->price) }} IQD) - {{ $plan->duration_days }} ڕۆژ
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- بەروارەکانی نوێکردنەوە (کراوەن و لۆک نین) -->
            <div class="grid grid-cols-2 gap-3 p-3.5 bg-slate-950/70 rounded-2xl border border-slate-800">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">بەرواری دەستپێکردن 🔓</label>
                    <input type="date" name="start_date" id="renew-start-date" onchange="onRenewStartDateChanged()" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono text-xs font-bold focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">بەرواری نوێی کۆتایی 🔓</label>
                    <input type="date" name="end_date" id="renew-end-date" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono text-xs font-bold focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">داشکاندن (IQD)</label>
                    <input type="number" name="discount" id="renew-discount" value="0" step="0.01" oninput="updateRenewTotal()" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-emerald-400 mb-1">بڕی دراو *</label>
                    <input type="number" name="paid_amount" id="renew-paid" step="0.01" required class="w-full bg-slate-800 border-2 border-indigo-500/50 rounded-xl px-3 py-2 text-emerald-300 text-xs font-black font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">شێوازی پارەدان *</label>
                <select name="payment_method" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs">
                    <option value="cash">💵 نەقد</option>
                    <option value="card">💳 کارت</option>
                    <option value="bank_transfer">🏦 حەواڵە</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" onclick="closeRenewModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700 transition">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-black text-white bg-indigo-600 hover:bg-indigo-500 transition shadow-lg shadow-indigo-600/30">
                    پەسەندکردنی نوێکردنەوە ✓
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal 3: ڕاگرتنی بەشداری (Freeze Modal) -->
<!-- ========================================================================= -->
<div id="freeze-modal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <div>
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <span>❄️ ڕاگرتنی بەشداریی یاریزان (Membership Freeze)</span>
                </h3>
                <p id="freeze-member-name" class="text-xs text-amber-400 font-bold mt-0.5"></p>
            </div>
            <button type="button" onclick="closeFreezeModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center text-lg">&times;</button>
        </div>

        <form id="freeze-form" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ژمارەی ڕۆژەکانی ڕاگرتن (Freeze Days):</label>
                <div class="flex gap-2 mb-2">
                    <button type="button" onclick="setFreezeDays(7)" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold">٧ ڕۆژ</button>
                    <button type="button" onclick="setFreezeDays(15)" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold">١٥ ڕۆژ</button>
                    <button type="button" onclick="setFreezeDays(30)" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold">٣٠ ڕۆژ</button>
                </div>
                <input type="number" name="days_count" id="freeze-days-input" min="1" max="90" value="15" required class="w-full bg-slate-800 border-2 border-slate-700 focus:border-amber-500 rounded-xl px-3 py-2 text-white font-black text-sm font-mono focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">هۆکاری ڕاگرتن *</label>
                <textarea name="reason" rows="2" required placeholder="گەشت، تاقیکردنەوە، نەخۆشی، هۆکاری تایبەت..." class="w-full bg-slate-800 border border-slate-700 focus:border-amber-500 rounded-xl px-3 py-2 text-white text-xs focus:outline-none"></textarea>
            </div>

            <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl text-xs text-amber-300 space-y-1">
                <span class="font-bold block">💡 تێبینی گرنگ:</span>
                <span class="block">بە ڕاگرتنی بەشدارییەکە، دەرگای هۆڵ بەڕووی یاریزانەکەدا دادەخرێت تا ئەو کاتەی چالاکی دەکاتەوە؛ لە کاتی دەستپێکردنەوەدا بە تەواوی ڕۆژە ڕاگیراوەکان بەرواری کۆتایی درێژ دەکرێتەوە.</span>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" onclick="closeFreezeModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700 transition">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-black text-white bg-amber-600 hover:bg-amber-500 transition shadow-lg shadow-amber-600/30">
                    پەسەندکردنی ڕاگرتن ❄️
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal 4: دەستپێکردنەوەی بەشداری (Unfreeze Confirmation Modal) -->
<!-- ========================================================================= -->
<div id="unfreeze-modal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <div>
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <span>▶️ دەستپێکردنەوەی بەشداریی ڕاگیراو</span>
                </h3>
                <p id="unfreeze-member-name" class="text-xs text-emerald-400 font-bold mt-0.5"></p>
            </div>
            <button type="button" onclick="closeUnfreezeModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center text-lg">&times;</button>
        </div>

        <form id="unfreeze-form" method="POST" action="" class="space-y-4">
            @csrf
            
            <div class="p-4 bg-emerald-500/10 border-2 border-emerald-500/30 rounded-2xl text-xs space-y-2">
                <span class="font-black text-emerald-300 block text-sm">✓ ئامادەیە بۆ چالاککردنەوە:</span>
                <span class="block text-slate-300">
                    کاتی دەستپێکردنی ڕاگرتن: <strong id="unfreeze-start-label" class="text-white font-mono">--</strong>
                </span>
                <span class="block text-slate-200">
                    بەرواری نوێی کۆتایی بەشداری: <strong id="unfreeze-projected-end" class="text-emerald-400 font-mono text-sm">--</strong>
                </span>
                <p class="text-[11px] text-slate-400 pt-1 border-t border-emerald-500/20">
                    بە کلیک لەسەر دوگمەی خوارەوە، ڕۆژە ڕاگیراوەکان دەخرێنە سەر بەرواری بەسەرچوون و یاریزانەکە ڕاستەوخۆ دەتوانێت دەرگای هۆڵ بکاتەوە.
                </p>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" onclick="closeUnfreezeModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700 transition">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 transition shadow-lg shadow-emerald-600/30 flex items-center gap-2">
                    <span>▶️ چالاککردنەوە تاکو بەرواری نوێ</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal 5: پیشاندانی کارتی QR Code -->
<!-- ========================================================================= -->
<div id="qr-modal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-sm w-full p-6 text-center space-y-4 shadow-2xl">
        <h3 id="qr-modal-title" class="text-base font-black text-white"></h3>
        <p id="qr-modal-code" class="text-xs font-mono text-emerald-400"></p>

        <div class="p-4 bg-white rounded-2xl inline-block shadow-2xl">
            <img id="qr-modal-img" src="" alt="QR Code" class="w-48 h-48 mx-auto">
        </div>

        <p class="text-[11px] text-slate-400">
            ئەم کۆدە پارێزراوە بە واژۆی HMAC-SHA256 بۆ کردنەوەی خێرای دەرگای زیرەکی هۆڵ.
        </p>

        <button type="button" onclick="closeQrModal()" class="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-xl text-xs transition">
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal 6: ئەرشیفی یاریزانە بەسەرچووەکان (15+ / 30+ / 60+ ڕۆژ) -->
<!-- ========================================================================= -->
<div id="expired-archive-modal" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 hidden flex items-center justify-center p-2 sm:p-4 overflow-y-auto">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-4xl w-full p-4 sm:p-6 space-y-4 shadow-2xl my-auto max-h-[92vh] flex flex-col relative overflow-hidden">
        
        <!-- Background Accent Glow -->
        <div class="absolute -top-32 -right-32 w-80 h-80 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header -->
        <div class="flex justify-between items-start gap-4 border-b border-slate-800 pb-3 relative">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-base">🗄️</span>
                    <h2 class="text-lg sm:text-xl font-black text-white">ئەرشیفی بەسەرچووە درێژخایەنەکان (15+ / 30+ / 60+ ڕۆژ)</h2>
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                        {{ $totalDormantCount }} یاریزان
                    </span>
                </div>
                <p class="text-xs text-slate-400">
                    ئەم یاریزانانە ماوەی بەسەرچوونیان زیاترە لە 15 ڕۆژ و لە خشتەی ڕۆژانەی هۆڵدا شاردراونەتەوە تاوەکو سیستەمەکە خاوێن بێت.
                </p>
            </div>
            <button type="button" onclick="closeExpiredArchiveModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center font-bold text-sm transition">
                &times;
            </button>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <div class="p-3 rounded-2xl bg-slate-800/80 border border-slate-700/60 text-center">
                <span class="text-[11px] text-slate-400 font-bold block">سەرجەم بەسەرچووەکان</span>
                <span class="text-xl font-black text-white font-mono">{{ $totalDormantCount }}</span>
                <span class="text-[10px] text-slate-500 block">15+ ڕۆژە نەهاتوون</span>
            </div>
            <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-center">
                <span class="text-[11px] text-amber-300 font-bold block">🟡 15 بۆ 30 ڕۆژ</span>
                <span class="text-xl font-black text-amber-400 font-mono">{{ $expired15To30->count() }}</span>
                <span class="text-[10px] text-amber-400/70 block">تازە بەسەرچووەکان</span>
            </div>
            <div class="p-3 rounded-2xl bg-orange-500/10 border border-orange-500/30 text-center">
                <span class="text-[11px] text-orange-300 font-bold block">🟠 30 بۆ 60 ڕۆژ</span>
                <span class="text-xl font-black text-orange-400 font-mono">{{ $expired30To60->count() }}</span>
                <span class="text-[10px] text-orange-400/70 block">ماوەی ناوەند</span>
            </div>
            <div class="p-3 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-center">
                <span class="text-[11px] text-rose-300 font-bold block">🔴 زیاتر لە 60 ڕۆژ</span>
                <span class="text-xl font-black text-rose-400 font-mono">{{ $expired60Plus->count() }}</span>
                <span class="text-[10px] text-rose-400/70 block">+60 ڕۆژ کۆنەکان</span>
            </div>
        </div>

        <!-- Toolbar & Filter Tabs & CSV Backup Export -->
        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-2 pt-1">
            <!-- Filter Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs font-bold">
                <button type="button" onclick="filterArchiveCategory('all')" id="archive-tab-all" class="archive-filter-btn px-3 py-1.5 rounded-xl bg-slate-700 text-white transition">
                    هەموو ({{ $totalDormantCount }})
                </button>
                <button type="button" onclick="filterArchiveCategory('15_30')" id="archive-tab-15_30" class="archive-filter-btn px-3 py-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white transition">
                    🟡 15 - 30 ڕۆژ ({{ $expired15To30->count() }})
                </button>
                <button type="button" onclick="filterArchiveCategory('30_60')" id="archive-tab-30_60" class="archive-filter-btn px-3 py-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white transition">
                    🟠 30 - 60 ڕۆژ ({{ $expired30To60->count() }})
                </button>
                <button type="button" onclick="filterArchiveCategory('60_plus')" id="archive-tab-60_plus" class="archive-filter-btn px-3 py-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white transition">
                    🔴 +60 ڕۆژ ({{ $expired60Plus->count() }})
                </button>
            </div>

            <!-- Instant Search & Backup Download -->
            <div class="flex items-center gap-2">
                <input type="text" id="archive-search-box" oninput="filterArchiveBySearch()" placeholder="🔍 گەڕان بەپێی ناو یان مۆبایل..." class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 w-full sm:w-52">
                
                <a href="{{ route('members.expired.export') }}" class="bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 flex-shrink-0" title="داگرتنی لیستی بەسەرچووەکان وەک فایلی CSV بۆ باک ئەپ">
                    <span>📥</span>
                    <span>باک ئەپ (CSV)</span>
                </a>
            </div>
        </div>

        <!-- Scrollable Members List -->
        <div class="flex-1 overflow-y-auto space-y-2 pr-1 max-h-[360px]" id="archive-members-container">
            @forelse($allDormantExpired as $exp)
                @php
                    $category = ($exp->days_expired < 30) ? '15_30' : (($exp->days_expired < 60) ? '30_60' : '60_plus');
                    $badgeColor = match($category) {
                        '15_30' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                        '30_60' => 'bg-orange-500/20 text-orange-300 border-orange-500/30',
                        '60_plus' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                    };

                    $rawPhone = preg_replace('/[^0-9]/', '', $exp->phone);
                    $waText = urlencode("سڵاو بەڕێز {$exp->full_name} گیان، هیوادارین لە تەندروستییەکی باشدا بیت. بەشدارییەکەت لە هۆڵی {$activeGym->name} پێش {$exp->days_expired} ڕۆژ بەسەرچووە. پێمان خۆشە دووبارە سەردانمان بکەیتەوە بۆ نوێکردنەوە و بەردەوامیدان بە وەرزشەکەت.");
                    $waUrl = "https://api.whatsapp.com/send?phone={$rawPhone}&text={$waText}";
                @endphp
                <div class="archive-member-item bg-slate-800/80 hover:bg-slate-800 border border-slate-700/60 rounded-2xl p-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 transition"
                     data-category="{{ $category }}"
                     data-search="{{ strtolower($exp->full_name . ' ' . $exp->phone . ' ' . $exp->member_code) }}">
                    
                    <!-- Player Info -->
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-slate-900 border border-slate-700 flex items-center justify-center font-bold text-sm text-slate-300 flex-shrink-0">
                            {{ mb_substr($exp->full_name, 0, 1) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white text-xs">{{ $exp->full_name }}</span>
                                <span class="text-[10px] font-mono text-slate-400 bg-slate-900 px-1.5 py-0.2 rounded">{{ $exp->member_code }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-0.5">
                                <span class="font-mono text-slate-300">📞 {{ $exp->phone }}</span>
                                <span>• پلانی کۆتایی: <span class="text-slate-200 font-semibold">{{ $exp->last_plan_name }}</span></span>
                                <span>• بەرواری کۆتایی: <span class="font-mono text-slate-300">{{ $exp->last_end_date }}</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Days Expired & Direct Actions -->
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
                        <div class="text-right">
                            <span class="inline-flex items-center gap-1 text-[11px] font-black px-2.5 py-1 rounded-xl border {{ $badgeColor }}">
                                <span>بەسەرچووە پێش:</span>
                                <span class="font-mono text-xs">{{ $exp->days_expired }}</span>
                                <span>ڕۆژ</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <!-- WhatsApp Reminder Link -->
                            <a href="{{ route('members.whatsapp.reminder', $exp->id) }}" target="_blank" class="bg-emerald-600/20 hover:bg-emerald-600/40 text-emerald-300 border border-emerald-500/30 px-2.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-sm" title="نامەی ئامادەکراوی بانگهێشتکردنەوە بە WhatsApp">
                                <span>📲</span>
                                <span class="hidden sm:inline">WhatsApp</span>
                            </a>

                            <!-- Renew Button -->
                            <button type="button" 
                                    onclick="closeExpiredArchiveModal(); openRenewModal({{ $exp->id }}, '{{ addslashes($exp->full_name) }}', {{ $exp->memberships->first()?->membership_plan_id ?? 'null' }}, '{{ $exp->last_end_date }}')"
                                    class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold px-3 py-1.5 rounded-xl text-xs transition shadow-md flex items-center gap-1">
                                <span>🔄</span>
                                <span>نوێکردنەوە</span>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-slate-500 text-xs">
                    هیچ یاریزانێکی بەسەرچووی 15+ ڕۆژ بوونی نییە. سەرجەم یاریزانەکان چالاکن یان تازەن!
                </div>
            @endforelse

            <div id="archive-empty-search" class="py-8 text-center text-slate-500 text-xs hidden">
                هیچ ئەنجامێک بەپێی ئەم فلتەرە نەدۆزرایەوە.
            </div>
        </div>

        <!-- Security & Audit Log Indicator -->
        <div class="pt-2 border-t border-slate-800 flex justify-between items-center text-[10px] text-slate-500">
            <span class="flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>پارێزراوە: هەموو سەردان و هەناردەکردنێکی ئەم ئەرشیفە لە لۆگی سیستەم (AuditLog) بە تەواوی تۆمار دەکرێت.</span>
            </span>
            <button type="button" onclick="closeExpiredArchiveModal()" class="text-slate-400 hover:text-white font-bold">
                داخستن
            </button>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    let activeRegPlanDays = 30;
    let activeRenewPlanDays = 30;

    // ==========================================
    // Register Member Plan & Date Management
    // ==========================================
    function openRegisterModal() {
        document.getElementById('register-modal').classList.remove('hidden');
        
        // هەڵبژاردنی یەکەمین پلانی بەردەست بە شێوازی بنەڕەتی
        const firstCard = document.querySelector('.reg-plan-card');
        if (firstCard) {
            firstCard.click();
        } else {
            recalculateRegEndDate();
        }
    }

    function closeRegisterModal() {
        document.getElementById('register-modal').classList.add('hidden');
    }

    function selectRegPlan(planId, price, durationDays, planName) {
        // Sync select
        const select = document.getElementById('reg-plan-select');
        select.value = planId;
        activeRegPlanDays = parseInt(durationDays);

        // Highlight selected plan card
        document.querySelectorAll('.reg-plan-card').forEach(c => {
            c.classList.remove('border-emerald-500', 'bg-emerald-950/20', 'ring-2', 'ring-emerald-500/40');
            c.classList.add('border-slate-700', 'bg-slate-900');
            const check = c.querySelector('.plan-check-badge');
            if (check) check.classList.add('hidden');
        });

        const activeCard = document.getElementById('reg-plan-card-' + planId);
        if (activeCard) {
            activeCard.classList.remove('border-slate-700', 'bg-slate-900');
            activeCard.classList.add('border-emerald-500', 'bg-emerald-950/20', 'ring-2', 'ring-emerald-500/40');
            const check = activeCard.querySelector('.plan-check-badge');
            if (check) check.classList.remove('hidden');
        }

        // Set price
        document.getElementById('reg-paid').value = price;
        document.getElementById('reg-discount').value = 0;

        // Auto calculate end date based on start date and plan duration
        recalculateRegEndDate();
    }

    function onRegPlanSelectChanged() {
        const select = document.getElementById('reg-plan-select');
        const opt = select.options[select.selectedIndex];
        if (opt) {
            selectRegPlan(select.value, opt.getAttribute('data-price'), opt.getAttribute('data-days'), opt.getAttribute('data-name'));
        }
    }

    function onRegStartDateChanged() {
        recalculateRegEndDate();
    }

    function recalculateRegEndDate() {
        const startDateInput = document.getElementById('reg-start-date');
        const endDateInput = document.getElementById('reg-end-date');
        const durationTag = document.getElementById('reg-duration-tag');

        const startDateVal = startDateInput.value || new Date().toISOString().split('T')[0];
        const startDate = new Date(startDateVal);

        if (!isNaN(startDate.getTime())) {
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + activeRegPlanDays);
            endDateInput.value = endDate.toISOString().split('T')[0];
            durationTag.textContent = `ماوە: ${activeRegPlanDays} ڕۆژ (تاکو ${endDateInput.value})`;
        }
    }

    function updateRegTotal() {
        const select = document.getElementById('reg-plan-select');
        const opt = select.options[select.selectedIndex];
        const price = parseFloat(opt ? opt.getAttribute('data-price') : 0);
        const discount = parseFloat(document.getElementById('reg-discount').value || 0);
        document.getElementById('reg-paid').value = Math.max(0, price - discount);
    }

    // ==========================================
    // Renew Membership Modal & Date Management
    // ==========================================
    function openRenewModal(memberId, memberName, planId, currentEndDate) {
        document.getElementById('renew-member-name').innerText = "یاریزان: " + memberName;
        document.getElementById('renew-form').action = "/members/" + memberId + "/renew";
        document.getElementById('renew-modal').classList.remove('hidden');

        // بەرواری دەستپێکردن: ئەگەر هێشتا بەسەرنەچووبێت لە بەرواری کۆتایی ئێستاوە دەستپێدەکات، دەنا لە ئەمڕۆوە
        const startDateInput = document.getElementById('renew-start-date');
        const todayStr = new Date().toISOString().split('T')[0];
        
        if (currentEndDate && currentEndDate >= todayStr) {
            startDateInput.value = currentEndDate;
        } else {
            startDateInput.value = todayStr;
        }

        if (planId) {
            document.getElementById('renew-plan-select').value = planId;
        }

        onRenewPlanChanged();
    }

    function closeRenewModal() {
        document.getElementById('renew-modal').classList.add('hidden');
    }

    function onRenewPlanChanged() {
        const select = document.getElementById('renew-plan-select');
        const opt = select.options[select.selectedIndex];
        if (opt) {
            const price = parseFloat(opt.getAttribute('data-price') || 0);
            activeRenewPlanDays = parseInt(opt.getAttribute('data-days') || 30);
            document.getElementById('renew-paid').value = price;
            document.getElementById('renew-discount').value = 0;
            recalculateRenewEndDate();
        }
    }

    function onRenewStartDateChanged() {
        recalculateRenewEndDate();
    }

    function recalculateRenewEndDate() {
        const startDateInput = document.getElementById('renew-start-date');
        const endDateInput = document.getElementById('renew-end-date');

        const startDateVal = startDateInput.value || new Date().toISOString().split('T')[0];
        const startDate = new Date(startDateVal);

        if (!isNaN(startDate.getTime())) {
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + activeRenewPlanDays);
            endDateInput.value = endDate.toISOString().split('T')[0];
        }
    }

    function updateRenewTotal() {
        const select = document.getElementById('renew-plan-select');
        const opt = select.options[select.selectedIndex];
        const price = parseFloat(opt ? opt.getAttribute('data-price') : 0);
        const discount = parseFloat(document.getElementById('renew-discount').value || 0);
        document.getElementById('renew-paid').value = Math.max(0, price - discount);
    }

    // ==========================================
    // Freeze & Unfreeze Modals
    // ==========================================
    function openFreezeModal(memberId, memberName, currentEndDate) {
        document.getElementById('freeze-member-name').innerText = "یاریزان: " + memberName + (currentEndDate ? " (کۆتایی ئێستا: " + currentEndDate + ")" : "");
        document.getElementById('freeze-form').action = "/members/" + memberId + "/freeze";
        document.getElementById('freeze-modal').classList.remove('hidden');
    }

    function closeFreezeModal() {
        document.getElementById('freeze-modal').classList.add('hidden');
    }

    function setFreezeDays(days) {
        document.getElementById('freeze-days-input').value = days;
    }

    function openUnfreezeModal(memberId, memberName, freezeStartDate, projectedEndDate) {
        document.getElementById('unfreeze-member-name').innerText = "یاریزان: " + memberName;
        document.getElementById('unfreeze-start-label').innerText = freezeStartDate || 'ئەمڕۆ';
        document.getElementById('unfreeze-projected-end').innerText = projectedEndDate || 'دیاریکراو بە ڕۆژانی ڕاگرتن';
        document.getElementById('unfreeze-form').action = "/members/" + memberId + "/unfreeze";
        document.getElementById('unfreeze-modal').classList.remove('hidden');
    }

    function closeUnfreezeModal() {
        document.getElementById('unfreeze-modal').classList.add('hidden');
    }

    // ==========================================
    // QR Modal
    // ==========================================
    function showQrModal(name, code, token) {
        document.getElementById('qr-modal-title').innerText = name;
        document.getElementById('qr-modal-code').innerText = "#" + code;
        document.getElementById('qr-modal-img').src = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" + encodeURIComponent(token);
        document.getElementById('qr-modal').classList.remove('hidden');
    }

    function closeQrModal() {
        document.getElementById('qr-modal').classList.add('hidden');
    }

    // ==========================================
    // Expired Members Archive Modal Logic
    // ==========================================
    let currentArchiveCategory = 'all';

    function openExpiredArchiveModal() {
        document.getElementById('expired-archive-modal').classList.remove('hidden');
        filterArchiveCategory('all');
        // Register view log on server quietly
        fetch("{{ route('members.index') }}?archive_viewed=1", { credentials: 'same-origin' }).catch(() => {});
    }

    function closeExpiredArchiveModal() {
        document.getElementById('expired-archive-modal').classList.add('hidden');
    }

    function filterArchiveCategory(category) {
        currentArchiveCategory = category;

        // Update active tab styles
        document.querySelectorAll('.archive-filter-btn').forEach(btn => {
            btn.classList.remove('bg-slate-700', 'text-white');
            btn.classList.add('bg-slate-800', 'text-slate-400');
        });

        const activeTab = document.getElementById('archive-tab-' + category);
        if (activeTab) {
            activeTab.classList.remove('bg-slate-800', 'text-slate-400');
            activeTab.classList.add('bg-slate-700', 'text-white');
        }

        applyArchiveFilters();
    }

    function filterArchiveBySearch() {
        applyArchiveFilters();
    }

    function applyArchiveFilters() {
        const query = (document.getElementById('archive-search-box').value || '').trim().toLowerCase();
        const items = document.querySelectorAll('.archive-member-item');
        let visibleCount = 0;

        items.forEach(item => {
            const itemCat = item.getAttribute('data-category');
            const itemSearch = item.getAttribute('data-search') || '';

            const matchesCategory = (currentArchiveCategory === 'all') || (itemCat === currentArchiveCategory);
            const matchesSearch = !query || itemSearch.includes(query);

            if (matchesCategory && matchesSearch) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        const emptyMessage = document.getElementById('archive-empty-search');
        if (emptyMessage) {
            emptyMessage.style.display = (visibleCount === 0 && items.length > 0) ? 'block' : 'none';
        }
    }
</script>
@endpush
