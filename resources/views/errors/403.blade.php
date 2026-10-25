@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto my-16 bg-slate-900 border border-rose-500/30 rounded-3xl p-8 text-center shadow-2xl space-y-4">
    <div class="w-16 h-16 rounded-2xl bg-rose-500/10 text-rose-400 border border-rose-500/20 flex items-center justify-center text-3xl mx-auto">
        🚫
    </div>
    
    <span class="text-xs px-2.5 py-1 rounded-full font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40">
        403 - ڕێگەپێنەدراو (Access Denied)
    </span>

    <h2 class="text-xl font-black text-white">
        دەسەڵاتی پێویستت نییە بۆ ئەم پەڕەیە
    </h2>

    <p class="text-xs text-slate-400 leading-relaxed max-w-md mx-auto">
        {{ $message ?? 'ڕۆڵ یان مۆڵەتەکانی هەژمارەکەت ڕێگە بە دەستگەیشتن بەم بەشە نادەن. تکایە پەیوەندی بە خاوەنی هۆڵ یان بەڕێوەبەری سەرەکییەوە بکە.' }}
    </p>

    @if(isset($permission))
        <div class="p-2.5 bg-slate-950/60 rounded-xl border border-slate-800 text-[11px] text-slate-400 font-mono inline-block">
            مۆڵەتی داواکراو: <span class="text-rose-400 font-bold">{{ $permission }}</span>
        </div>
    @endif

    <div class="pt-4 flex justify-center gap-3">
        <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-slate-800 hover:bg-slate-700 transition">
            گەڕانەوە بۆ داشبۆرد
        </a>
    </div>
</div>
@endsection
