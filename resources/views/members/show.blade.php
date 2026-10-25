@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Top Breadcrumb & Action Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('members.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold transition flex items-center gap-1">
                    <span>← گەڕانەوە بۆ یاریزانان</span>
                </a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-slate-400">پرۆفایلی یاریزان</span>
            </div>
            <h1 class="text-2xl font-black text-white mt-1 flex items-center gap-2">
                <span>👤 {{ $member->full_name }}</span>
                <span class="text-xs font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2.5 py-1 rounded-xl">
                    #{{ $member->member_code }}
                </span>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Print ID Card Button -->
            <a href="{{ route('members.card', $member->id) }}" target="_blank" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-2xl text-xs font-bold transition shadow-lg shadow-purple-600/30 flex items-center gap-1.5">
                <span>🪪</span>
                <span>چاپی کارتی ئەندامێتی (ID Card)</span>
            </a>

            <!-- WhatsApp Receipt / Reminder -->
            <a href="{{ route('members.whatsapp.receipt', $member->id) }}" target="_blank" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-bold transition shadow-lg shadow-emerald-600/30 flex items-center gap-1.5">
                <span>💬</span>
                <span>وەسڵی WhatsApp</span>
            </a>
        </div>
    </div>

    <!-- Member Overview Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Member Identity & Contact Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-2xl font-black text-white shadow-lg shadow-emerald-600/20">
                    {{ mb_substr($member->full_name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-lg font-black text-white">{{ $member->full_name }}</h2>
                    <span class="text-xs font-mono text-slate-400">📱 {{ $member->phone }}</span>
                    <div class="mt-1">
                        @if($member->status === 'active')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                ● بەشداری چالاکە
                            </span>
                        @elseif($member->status === 'frozen')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                ❄️ بەشداری ڕاگیراوە
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                ⚠️ بەسەرچووە
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-4 space-y-2.5 text-xs">
                <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">ڕەگەز:</span>
                    <span class="font-bold text-white">{{ $member->gender === 'male' ? 'نێر (کوڕ)' : 'مێ (کچ)' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">کەسێک بۆ کاتی پێویست:</span>
                    <span class="font-bold text-white">{{ $member->emergency_contact_name ?? '—' }} ({{ $member->emergency_contact_phone ?? '—' }})</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">قەرزی کەڵەکەبوو:</span>
                    <span class="font-bold font-mono {{ $member->current_debt > 0 ? 'text-amber-400' : 'text-emerald-400' }}">
                        {{ number_format($member->current_debt) }} IQD
                    </span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-400">تۆمارکراوە لەلایەن:</span>
                    <span class="font-bold text-slate-300">{{ $member->gym->name ?? 'GymPro' }}</span>
                </div>
            </div>
        </div>

        <!-- Center & Right: Active Membership & Quick Renewal -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center justify-between border-b border-slate-800 pb-3">
                <span class="flex items-center gap-2">
                    <span>🎫</span>
                    <span>زانیاری بەشداری ئێستا و کۆتایی</span>
                </span>
                
                @if($member->activeMembership)
                    <button type="button" onclick="document.getElementById('freeze-modal-{{ $member->id }}').classList.remove('hidden')" class="px-3 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-xl text-xs font-bold hover:bg-amber-500/30 transition">
                        ❄️ ڕاگرتنی کاتی (Freeze)
                    </button>
                @endif
            </h3>

            @if($member->activeMembership)
                @php
                    $activeMs = $member->activeMembership;
                    $daysRemaining = (int) now()->diffInDays($activeMs->end_date, false);
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60">
                        <span class="text-[11px] text-slate-400 font-bold block mb-1">پلانی بەشداری</span>
                        <span class="text-base font-black text-white">{{ $activeMs->plan->name ?? 'پلانی تایبەت' }}</span>
                        <span class="text-xs font-mono text-emerald-400 block mt-1">{{ number_format($activeMs->paid_price) }} IQD</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60">
                        <span class="text-[11px] text-slate-400 font-bold block mb-1">بەرواری دەستپێک و کۆتایی</span>
                        <span class="text-xs font-mono text-slate-200 block">{{ $activeMs->start_date->format('Y-m-d') }}</span>
                        <span class="text-sm font-black text-amber-400 block mt-0.5">تاکو: {{ $activeMs->end_date->format('Y-m-d') }}</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60">
                        <span class="text-[11px] text-slate-400 font-bold block mb-1">ماوەی ماوە</span>
                        <span class="text-2xl font-black font-mono {{ $daysRemaining <= 3 ? 'text-rose-400' : 'text-emerald-400' }}">
                            {{ max(0, $daysRemaining) }}
                        </span>
                        <span class="text-xs text-slate-400 mr-1">ڕۆژ ماوە</span>
                    </div>
                </div>
            @else
                <div class="p-6 text-center rounded-2xl bg-slate-800/40 border border-dashed border-slate-700 text-slate-400 text-xs">
                    ⚠️ ئەم یاریزانە لە ئێستادا هیچ بەشدارییەکی چالاکی نییە یان کاتەکەی بەسەرچووە.
                </div>
            @endif

            <!-- Quick Renew Form -->
            <div class="pt-4 border-t border-slate-800">
                <h4 class="text-xs font-bold text-slate-300 mb-3 flex items-center gap-1.5">
                    <span>⚡ نوێکردنەوەی خێرای بەشداری (Quick Renewal):</span>
                </h4>

                <form action="{{ route('members.renew', $member->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    @csrf
                    <div>
                        <select name="plan_id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500 font-sans">
                            <option value="">هەڵبژاردنی پلان...</option>
                            @foreach($plans as $p)
                                <option value="{{ $p->id }}" {{ $member->activeMembership?->membership_plan_id == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ number_format($p->price) }} IQD)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <input type="number" name="paid_amount" placeholder="بڕی پارەدان..." required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-mono focus:outline-none focus:border-emerald-500">
                    </div>

                    <div>
                        <select name="payment_method" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500">
                            <option value="cash">کاش (نەقد)</option>
                            <option value="card">کارت (POS)</option>
                            <option value="bank_transfer">حەواڵە</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 rounded-xl text-xs transition shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-1.5">
                            <span>🔄 نوێکردنەوە</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Timeline Tabs: All Past Memberships & Attendance History -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-6">
        
        <!-- Tab 1: Mێژووی تەواوی بەشدارییەکان (Subscription History) -->
        <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2 mb-4">
                <span>📜 مێژووی تەواوی بەشداری و نوێکردنەوەکان (Subscription Lifecycle)</span>
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="bg-slate-850 border-b border-slate-800 text-slate-400 font-bold">
                            <th class="p-3.5">پلان</th>
                            <th class="p-3.5">دەستپێک</th>
                            <th class="p-3.5">بەسەرچوون</th>
                            <th class="p-3.5">بڕی دراو</th>
                            <th class="p-3.5">دۆخ</th>
                            <th class="p-3.5">تۆمارکرا لەلایەن</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($member->memberships as $ms)
                            <tr class="hover:bg-slate-850/50 transition">
                                <td class="p-3.5 font-bold text-white">{{ $ms->plan->name ?? 'پلانی تایبەت' }}</td>
                                <td class="p-3.5 font-mono text-slate-300">{{ $ms->start_date->format('Y-m-d') }}</td>
                                <td class="p-3.5 font-mono font-bold text-amber-400">{{ $ms->end_date->format('Y-m-d') }}</td>
                                <td class="p-3.5 font-mono text-emerald-400">{{ number_format($ms->paid_price) }} IQD</td>
                                <td class="p-3.5">
                                    @if($ms->status === 'active' && $ms->end_date->isFuture())
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300">چالاک</span>
                                    @elseif($ms->status === 'frozen')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300">ڕاگیراو</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">بەسەرچوو</span>
                                    @endif
                                </td>
                                <td class="p-3.5 text-slate-400 text-[11px]">{{ $ms->createdBy->name ?? 'سیستەم' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500 text-xs">هیچ بەشدارییەکی پێشوو تۆمار نەکراوە.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 2: هاتنی ڕۆژانە و مێژووی دەرگا (Attendance History) -->
        <div class="border-t border-slate-800 pt-6">
            <h3 class="text-base font-bold text-white flex items-center gap-2 mb-4">
                <span>🚪 مێژووی سەردانەکانی یاریزان (Attendance History - دواین ٣٠ سەردان)</span>
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="bg-slate-850 border-b border-slate-800 text-slate-400 font-bold">
                            <th class="p-3.5">ڕۆژ و ڕێکەوت</th>
                            <th class="p-3.5">کاتی هاتنەژوورەوە</th>
                            <th class="p-3.5">کاتی دەرچوون</th>
                            <th class="p-3.5">ماوە لە هۆڵ</th>
                            <th class="p-3.5">دۆخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($member->attendances as $att)
                            <tr class="hover:bg-slate-850/50 transition">
                                <td class="p-3.5 font-mono text-slate-300">{{ $att->date ?? $att->created_at->format('Y-m-d') }}</td>
                                <td class="p-3.5 font-mono text-emerald-400 font-bold">{{ $att->entry_time ? \Carbon\Carbon::parse($att->entry_time)->format('h:i A') : '—' }}</td>
                                <td class="p-3.5 font-mono text-amber-400">{{ $att->exit_time ? \Carbon\Carbon::parse($att->exit_time)->format('h:i A') : '—' }}</td>
                                <td class="p-3.5 font-mono text-slate-200">
                                    {{ $att->duration_minutes ? floor($att->duration_minutes / 60) . ' کاتژمێر و ' . ($att->duration_minutes % 60) . ' خولەک' : '—' }}
                                </td>
                                <td class="p-3.5">
                                    @if($att->status === 'INSIDE')
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 animate-pulse">لە ژوورەوەیە 🟢</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">تەواوبوو</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-500 text-xs">تا ئێستا هیچ سەردانێکی دەرگا بۆ ئەم ئەندامە تۆمار نەکراوە.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- Freeze Modal -->
@if($member->activeMembership)
<div id="freeze-modal-{{ $member->id }}" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>❄️ ڕاگرتنی کاتی بەشداری (Freeze Membership)</span>
            </h3>
            <button onclick="document.getElementById('freeze-modal-{{ $member->id }}').classList.add('hidden')" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('members.freeze', $member->id) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ژمارەی ڕۆژەکانی ڕاگرتن *</label>
                <select name="days_count" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-amber-500">
                    <option value="7">٧ ڕۆژ (١ هەفتە)</option>
                    <option value="14">١٤ ڕۆژ (٢ هەفتە)</option>
                    <option value="21">٢١ ڕۆژ (٣ هەفتە)</option>
                    <option value="30">٣٠ ڕۆژ (١ مانگ)</option>
                </select>
                <span class="text-[10px] text-slate-400 mt-1 block">بە درێژایی ئەم ماوەیە، بەرواری بەسەرچوونی بەشدارییەکەی بۆ درێژ دەکرێتەوە.</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">هۆکاری ڕاگرتن *</label>
                <input type="text" name="reason" required placeholder="وەک: گەشت، نەخۆشی، تاقیکردنەوە..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-amber-500">
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('freeze-modal-{{ $member->id }}').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 shadow-lg shadow-amber-600/30">
                    تەواوکردنی ڕاگرتن
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
