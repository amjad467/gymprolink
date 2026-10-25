@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto my-12 bg-slate-900 border border-slate-800 rounded-3xl p-8 text-center shadow-2xl">
    <div class="w-16 h-16 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4 text-3xl">
        🛍️
    </div>
    <h2 class="text-xl font-bold text-white mb-2">
        هیچ خاڵێکی فرۆشتن (شۆپ) بۆ ئەم هۆڵە چالاک نەکراوە
    </h2>
    <p class="text-xs text-slate-400 mb-6 leading-relaxed">
        هۆڵی هەڵبژێردراو (<span class="text-indigo-400 font-bold">{{ $activeGym->name ?? 'هۆڵ' }}</span>) تا ئێستا شۆپی تەواوکەری خۆراکی بۆ دروست نەکراوە یان ناچالاکە.
    </p>

    <div class="flex justify-center gap-3">
        <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
            گەڕانەوە بۆ داشبۆرد
        </a>
        <a href="{{ route('inventory.index') }}" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-lg shadow-indigo-600/30">
            بەڕێوەبردنی کۆگا و کاڵاکان
        </a>
    </div>
</div>
@endsection
