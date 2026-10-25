@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Header -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden">
        <div class="absolute -top-20 -left-20 w-64 h-64 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center justify-center font-black text-2xl">
                    💾
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-white">
                        باک ئەپی تەواوەتی داتابەیس و داتای سیستەم (System Backups)
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">
                        دروستکردنی کۆپی یەدەگ بۆ تەواوی داتای یاریزانان، بەشدارییەکان، فرۆشتنەکانی شۆپ، کۆگا، و ڕووداوەکانی دەرگا
                    </p>
                </div>
            </div>

            <!-- Create Backup Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Create DB Backup -->
                <form action="{{ route('backups.create') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="db">
                    <button type="submit" class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-xs sm:text-sm px-4 py-2.5 rounded-2xl shadow-lg shadow-emerald-600/30 transition flex items-center gap-2">
                        <span>💾</span>
                        <span>باک ئەپی داتابەیس (DB)</span>
                    </button>
                </form>

                <!-- Create JSON Archive -->
                <form action="{{ route('backups.create') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="json">
                    <button type="submit" class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-black text-xs sm:text-sm px-4 py-2.5 rounded-2xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                        <span>📄</span>
                        <span>ئەرشیفی داتا (JSON)</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Security & Storage Notice -->
    <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 text-xs text-slate-400 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div class="flex items-center gap-2">
            <span class="text-emerald-400 font-bold text-sm">🛡️ سکیوریتی و پاراستن:</span>
            <span>هەموو باک ئەپەکان بە کۆدی تایبەت و کاتی دروستبوون تۆمار دەکرێن و گشت چالاکییەکان لە AuditLog چاودێری دەکرێن.</span>
        </div>
        <span class="bg-slate-800 px-3 py-1 rounded-xl text-[11px] text-slate-300 font-mono font-bold flex-shrink-0">
            {{ count($backups) }} فایلی پاشەکەوتکراو
        </span>
    </div>

    <!-- Existing Backups Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-950/80 text-slate-400 border-b border-slate-800 text-[11px] font-black uppercase">
                        <th class="p-4">ناوی فایل (Filename)</th>
                        <th class="p-4">جۆری باک ئەپ</th>
                        <th class="p-4 font-mono">قەبارەی فایل</th>
                        <th class="p-4">کاتی دروستکردن</th>
                        <th class="p-4 text-center">کردارەکان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($backups as $b)
                        <tr class="hover:bg-slate-800/40 transition">
                            <!-- Filename -->
                            <td class="p-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-base">{{ $b['extension'] === 'sqlite' ? '💾' : '📄' }}</span>
                                    <span class="font-mono font-bold text-white text-xs">{{ $b['filename'] }}</span>
                                </div>
                            </td>

                            <!-- Type -->
                            <td class="p-4 font-bold text-slate-300">
                                <span class="px-2.5 py-1 rounded-xl text-[10px] {{ $b['extension'] === 'sqlite' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' }}">
                                    {{ $b['type'] }}
                                </span>
                            </td>

                            <!-- Size -->
                            <td class="p-4 font-mono font-bold text-emerald-400">
                                {{ $b['size_formatted'] }}
                            </td>

                            <!-- Date Created -->
                            <td class="p-4 text-slate-300 font-mono text-[11px]">
                                {{ $b['created_at'] }}
                            </td>

                            <!-- Actions (Download & Delete) -->
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('backups.download', $b['filename']) }}" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-1.5 rounded-xl text-xs transition shadow-md flex items-center gap-1">
                                        <span>📥</span>
                                        <span>داگرتن</span>
                                    </a>

                                    @if($b['extension'] === 'sqlite')
                                        <form action="{{ route('backups.restore', $b['filename']) }}" method="POST" onsubmit="return confirm('⚠️ ئاگاداری گرنگ: گەڕاندنەوەی ئەم باک ئەپە داتابەیسی ئێستا دەگۆڕێت بە داتای ئەم بەروارە. بەر لە جێبەجێکردن کۆپیەکی سەلامەتی هەڵدەگیرێت. ئایا دڵنیایت؟')">
                                            @csrf
                                            <button type="submit" class="bg-amber-600/30 hover:bg-amber-600 text-amber-300 hover:text-white border border-amber-500/30 px-2.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1" title="گەڕاندنەوەی داتابەیس">
                                                <span>🔄</span>
                                                <span>گەڕاندنەوە</span>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('backups.destroy', $b['filename']) }}" method="POST" onsubmit="return confirm('ئایا دڵنیایت لە سڕینەوەی ئەم فایلی باک ئەپە؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-rose-600/30 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 px-2.5 py-1.5 rounded-xl text-xs font-bold transition" title="سڕینەوە">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-slate-500 text-xs">
                                تا ئێستا هیچ فایلی باک ئەپێک دروست نەکراوە. لە دوگمەکانی سەرەوە باک ئەپێک دروست بکە!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
