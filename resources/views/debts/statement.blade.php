@extends('layouts.app')

@section('title', 'کەشف حیسابی یاریزان: ' . $member->full_name)

@section('content')
<div class="space-y-6">

    <!-- سەرپەڕ و گەڕانەوە -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-slate-900/90 border border-slate-800 p-6 rounded-3xl shadow-xl backdrop-blur-md">
        <div class="flex items-center gap-3">
            <a href="{{ route('debts.index') }}" class="w-10 h-10 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center font-bold text-lg transition border border-slate-700">
                ←
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-black text-white tracking-tight">کەشف حیسابی دارایی</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        #{{ $member->member_code }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    مێژووی تەواوی کڕینەکان بە قەرز، فاکتۆرەکان و سەرجەم وەصڵەکانی دانەوە
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
                <span>🖨️</span>
                <span>چاپی کەشف حیساب</span>
            </button>
            <a href="{{ route('debts.whatsapp.reminder', $member->id) }}" target="_blank"
                class="px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-emerald-600/20">
                <span>📲</span>
                <span>نامەی WhatsApp</span>
            </a>
        </div>
    </div>

    <!-- کورتهێڵ و کارتی زانیاری یاریزان -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- زانیاری کەسی -->
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-2">
            <span class="text-xs text-slate-400 font-bold block">زانیاری یاریزان:</span>
            <div class="text-lg font-black text-white">{{ $member->full_name }}</div>
            <div class="text-xs text-slate-400 font-mono">📞 {{ $member->phone }}</div>
            <div class="text-xs text-slate-400">
                دۆخی بەشداری:
                <strong class="{{ $member->status === 'active' ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $member->status === 'active' ? 'چالاک' : 'بەسەرچوو' }}
                </strong>
            </div>
        </div>

        <!-- قەرزی هەنووکەیی -->
        <div class="p-5 rounded-3xl bg-rose-500/10 border border-rose-500/30 flex flex-col justify-between">
            <span class="text-xs font-bold text-rose-300">کۆی قەرزی ماوەی سەر ئەندام:</span>
            <div class="my-2">
                <span class="text-3xl font-black text-rose-400 font-mono">{{ number_format($member->current_debt) }}</span>
                <span class="text-xs text-rose-300/80 mr-1">IQD</span>
            </div>
            <span class="text-[11px] text-rose-300/70">
                سنووری ڕێگەپێدراوی قەرز: {{ number_format($member->credit_limit) }} IQD
            </span>
        </div>

        <!-- کۆی پارەدانەکان -->
        <div class="p-5 rounded-3xl bg-emerald-500/10 border border-emerald-500/30 flex flex-col justify-between">
            <span class="text-xs font-bold text-emerald-300">کۆی پارەی پاکتاوکراوی کۆن:</span>
            <div class="my-2">
                <span class="text-3xl font-black text-emerald-400 font-mono">
                    {{ number_format($payments->where('payment_method', 'debt_settlement')->sum('amount')) }}
                </span>
                <span class="text-xs text-emerald-300/80 mr-1">IQD</span>
            </div>
            <span class="text-[11px] text-emerald-300/70">
                {{ $payments->count() }} پسووڵەی دانەوەی قەرز تۆمارکراوە
            </span>
        </div>
    </div>

    <!-- بەشی ١: فرۆشتنەکانی شۆپ بە قەرز (POS Credit Sales) -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base">🛍️</span>
                <h3 class="text-sm font-black text-white">کڕینەکانی شۆپ بە قەرز (POS Credit Purchases)</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                    {{ $creditSales->count() }} فاکتۆر
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-400 border-b border-slate-800">
                        <th class="py-3 px-4 font-bold">ژ. فاکتۆر</th>
                        <th class="py-3 px-4 font-bold">بەروار و کات</th>
                        <th class="py-3 px-4 font-bold">کاڵاکان</th>
                        <th class="py-3 px-4 font-bold text-left">کۆی گشتی (IQD)</th>
                        <th class="py-3 px-4 font-bold text-center">پسووڵە</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($creditSales as $sale)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-300">#{{ $sale->invoice_number }}</td>
                            <td class="py-3 px-4 text-slate-400 font-mono">{{ $sale->created_at->format('Y-m-d h:i A') }}</td>
                            <td class="py-3 px-4 text-slate-300">
                                @foreach($sale->items as $it)
                                    <span class="inline-block bg-slate-800 px-2 py-0.5 rounded text-[11px] font-mono mr-1">
                                        {{ $it->variant->product->name_ku ?? $it->variant->product->name_en }} ({{ (int)$it->quantity }}x)
                                    </span>
                                @endforeach
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-black text-rose-400">
                                {{ number_format($sale->total_amount) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('pos.invoices.print', $sale->id) }}" target="_blank"
                                    class="px-2.5 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 text-[11px] font-bold">
                                    🖨️ پسووڵە
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">هیچ کڕینێکی شۆپ بە قەرز بوونی نییە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- بەشی ٢: مێژووی دانەوەی قەرز (Repayments History) -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base">💵</span>
                <h3 class="text-sm font-black text-white">مێژووی وەسڵەکانی دانەوە و پاکتاوی قەرز (Repayment Receipts)</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-emerald-400 border border-slate-700">
                    {{ $payments->count() }} وەسڵ
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-400 border-b border-slate-800">
                        <th class="py-3 px-4 font-bold">ژمارەی وەسڵ</th>
                        <th class="py-3 px-4 font-bold">بەروار و کات</th>
                        <th class="py-3 px-4 font-bold">شێوازی دانەوە</th>
                        <th class="py-3 px-4 font-bold">وەرگیراوە لەلایەن</th>
                        <th class="py-3 px-4 font-bold text-left">بڕی دراو (IQD)</th>
                        <th class="py-3 px-4 font-bold text-center">چاپ و واتسئاپ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($payments as $pay)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-mono font-bold text-emerald-400">#{{ $pay->reference_number }}</td>
                            <td class="py-3 px-4 text-slate-400 font-mono">{{ $pay->created_at->format('Y-m-d h:i A') }}</td>
                            <td class="py-3 px-4 text-slate-300">
                                @if($pay->payment_method === 'cash' || $pay->payment_method === 'debt_settlement')
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-bold">💵 نەقد</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 text-[10px] font-bold">💳 کارت / بانک</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-400 font-bold">{{ $pay->receiver->name ?? 'کاشێر' }}</td>
                            <td class="py-3 px-4 text-left font-mono font-black text-emerald-400">
                                +{{ number_format($pay->amount) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('debts.payments.print', $pay->id) }}" target="_blank"
                                        class="px-2 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 text-[11px] font-bold">
                                        🖨️ چاپ
                                    </a>
                                    <a href="{{ route('debts.payments.whatsapp', $pay->id) }}" target="_blank"
                                        class="px-2 py-1 rounded-xl bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 text-[11px] font-bold">
                                        📲 واتسئاپ
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">هیچ وەسڵێکی دانەوەی قەرز بۆ ئەم یاریزانە تۆمار نەکراوە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
