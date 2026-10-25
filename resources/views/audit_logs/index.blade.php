@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>🛡️ تۆماری چاودێری و کردارە هەستیارەکان (Audit Trail Logs)</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                تۆمارکردنی ئۆتۆماتیکی و پارێزراوی هەموو لۆگین، پارەدان، خەرجی، گۆڕانکاری مۆڵەت و گەڕاندنەوەی داتابەیس
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-3 py-1.5 rounded-xl">
                🔒 پارێزراو بەپێی ستاندارد
            </span>
        </div>
    </div>

    <!-- KPI Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-lg">
            <div class="flex justify-between items-center text-slate-400 text-xs font-bold mb-2">
                <span>چالاکییەکانی ئەمڕۆ</span>
                <span class="text-xl">📅</span>
            </div>
            <div class="text-2xl font-black text-white font-mono">{{ number_format($stats['total_today']) }}</div>
            <div class="text-[11px] text-slate-500 mt-1 font-sans">تۆمار لە سەرەتای ئەمڕۆوە</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-lg">
            <div class="flex justify-between items-center text-indigo-400 text-xs font-bold mb-2">
                <span>چوونەژوورەوە و ئاسایش</span>
                <span class="text-xl">🔐</span>
            </div>
            <div class="text-2xl font-black text-indigo-300 font-mono">{{ number_format($stats['auth_events']) }}</div>
            <div class="text-[11px] text-slate-500 mt-1 font-sans">لۆگین، 2FA و دەرچوون</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-lg">
            <div class="flex justify-between items-center text-emerald-400 text-xs font-bold mb-2">
                <span>کردارە داراییەکان</span>
                <span class="text-xl">💰</span>
            </div>
            <div class="text-2xl font-black text-emerald-400 font-mono">{{ number_format($stats['financial_events']) }}</div>
            <div class="text-[11px] text-slate-500 mt-1 font-sans">پارەدان، قەرز، خەرجی و Z-Report</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-lg">
            <div class="flex justify-between items-center text-amber-400 text-xs font-bold mb-2">
                <span>ئاگادارییە سکیوریتییەکان</span>
                <span class="text-xl">⚠️</span>
            </div>
            <div class="text-2xl font-black text-amber-400 font-mono">{{ number_format($stats['security_events']) }}</div>
            <div class="text-[11px] text-slate-500 mt-1 font-sans">قوفڵکردن، باک ئەپ و ڕیستۆر</div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-xl">
        <form action="{{ route('audit-logs.index') }}" method="GET" class="flex flex-wrap items-center justify-between gap-3">
            
            <!-- Category Tabs -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs font-bold">
                <a href="{{ route('audit-logs.index') }}" class="px-3 py-1.5 rounded-xl transition {{ empty($category) ? 'bg-emerald-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                    هەموو کردارەکان
                </a>
                <a href="{{ route('audit-logs.index', ['category' => 'finance']) }}" class="px-3 py-1.5 rounded-xl transition {{ $category === 'finance' ? 'bg-emerald-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                    💰 دارایی و قەرز
                </a>
                <a href="{{ route('audit-logs.index', ['category' => 'auth']) }}" class="px-3 py-1.5 rounded-xl transition {{ $category === 'auth' ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                    🔐 چوونەژوورەوە و 2FA
                </a>
                <a href="{{ route('audit-logs.index', ['category' => 'security']) }}" class="px-3 py-1.5 rounded-xl transition {{ $category === 'security' ? 'bg-amber-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                    🛡️ باک ئەپ و سکیوریتی
                </a>
                <a href="{{ route('audit-logs.index', ['category' => 'members']) }}" class="px-3 py-1.5 rounded-xl transition {{ $category === 'members' ? 'bg-teal-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                    👥 ئەندامان و بەشداری
                </a>
            </div>

            <!-- Search box -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <input type="text" 
                       name="search" 
                       value="{{ $search ?? '' }}" 
                       placeholder="گەڕان بەپێی کردار، بەکارهێنەر، IP..." 
                       class="w-full sm:w-64 bg-slate-800 border border-slate-700 rounded-2xl px-3.5 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 font-sans">
                
                @if(!empty($category))
                    <input type="hidden" name="category" value="{{ $category }}">
                @endif

                <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-2xl text-xs transition border border-slate-700">
                    🔍
                </button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-850 border-b border-slate-800 text-slate-400 font-bold">
                        <th class="p-4">بەروار و کات</th>
                        <th class="p-4">بەکارهێنەر</th>
                        <th class="p-4">کرداری ئەنجامدراو</th>
                        <th class="p-4">ناونیشانی IP</th>
                        <th class="p-4">وردەکاری و گۆڕانکارییەکان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($logs as $log)
                        @php
                            $actionColor = 'bg-slate-800 text-slate-300 border-slate-700';
                            if (str_contains($log->action, 'EXPENSE') || str_contains($log->action, 'PAYMENT') || str_contains($log->action, 'DEBT')) {
                                $actionColor = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
                            } elseif (str_contains($log->action, 'RESTORE') || str_contains($log->action, 'LOCKED')) {
                                $actionColor = 'bg-rose-500/10 text-rose-400 border-rose-500/30';
                            } elseif (str_contains($log->action, 'auth.')) {
                                $actionColor = 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30';
                            } elseif (str_contains($log->action, 'BACKUP')) {
                                $actionColor = 'bg-amber-500/10 text-amber-400 border-amber-500/30';
                            }
                        @endphp
                        <tr class="hover:bg-slate-850/50 transition">
                            
                            <!-- Timestamp -->
                            <td class="p-4 text-slate-300 font-mono text-[11px] whitespace-nowrap">
                                <div>{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                                <div class="text-[10px] text-slate-500">{{ $log->created_at->diffForHumans() }}</div>
                            </td>

                            <!-- User -->
                            <td class="p-4 whitespace-nowrap">
                                @if($log->user)
                                    <div class="font-bold text-white">{{ $log->user->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-slate-500 italic">سیستەم / لۆگینی نەناسراو</span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-xl text-[11px] font-mono font-bold border inline-block {{ $actionColor }}">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <!-- IP Address -->
                            <td class="p-4 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                🌐 {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>

                            <!-- Payload / Details -->
                            <td class="p-4">
                                @if($log->new_values || $log->old_values)
                                    <details class="cursor-pointer group">
                                        <summary class="text-[11px] font-bold text-slate-400 hover:text-emerald-400 transition list-none flex items-center gap-1">
                                            <span>📄 پیشاندانی وردەکاری</span>
                                            <span class="text-[9px] group-open:rotate-180 transition-transform">▼</span>
                                        </summary>
                                        <div class="mt-2 p-3 rounded-2xl bg-slate-950 border border-slate-800 text-[10px] font-mono text-emerald-400 overflow-x-auto max-w-md">
                                            @if($log->old_values)
                                                <div class="text-rose-400 mb-1 font-bold">بڕی پێشوو:</div>
                                                <pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @endif
                                            @if($log->new_values)
                                                <div class="text-emerald-400 mt-1 mb-1 font-bold">گۆڕانکاری نوێ:</div>
                                                <pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    <span class="text-[10px] text-slate-600">—</span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-slate-500 text-xs">
                                هیچ تۆمارێکی لۆگ بەپێی ئەم فلتەرە نەدۆزرایەوە.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-800">
            {{ $logs->links() }}
        </div>
    </div>

</div>
@endsection
