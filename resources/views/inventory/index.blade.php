@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>📦 بەڕێوەبردنی کۆگا، ناساندنی کاڵاکان و کڕین (Inventory & Supply)</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                ناساندنی تەواوکەرە خۆراکییەکان بە سێ زمان، بارکۆد، تام و قەبارە، و تۆمارکردنی فاکتۆری کڕین بەپێی بەرواری بەسەرچوون (FEFO) لە {{ $activeGym->name }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button onclick="openCategoryModal()" class="bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-lg shadow-amber-600/20 transition flex items-center gap-1.5">
                <span>🏷️ زیادکردنی پۆلێن (Category)</span>
            </button>
            <button onclick="openBrandModal()" class="bg-violet-600 hover:bg-violet-500 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-lg shadow-violet-600/20 transition flex items-center gap-1.5">
                <span>✨ زیادکردنی براند (Brand)</span>
            </button>
            <button onclick="openProductModal()" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-lg shadow-indigo-600/20 transition flex items-center gap-1.5">
                <span>➕ ناساندنی کاڵای نوێ</span>
            </button>
            <button onclick="openBatchModal()" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-lg shadow-emerald-600/20 transition flex items-center gap-1.5">
                <span>📥 وەرگرتنی کڕین (وەجبەی نوێ)</span>
            </button>
            <button onclick="openSupplierModal()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5">
                <span>🏢 دابینکەری نوێ</span>
            </button>
        </div>
    </div>

    <!-- ئاگاداری کەمی کۆگا و فلتەری خێرا (Low Stock Alert Banner) -->
    @if(isset($lowStockCount) && $lowStockCount > 0)
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl border border-amber-500/30 shrink-0">
                    ⚠️
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-300">ئاگاداری کەمبوونەوەی کاڵا لە کۆگادا!</h3>
                    <p class="text-xs text-amber-400/80">
                        <span class="font-bold font-mono">{{ $lowStockCount }}</span> جۆری کاڵا گەیشتوونەتە ئاستی دیاریکراوی کەمبوونەوە (Minimum Alert) یان تەواوبوون.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('inventory.index', ['filter' => 'low_stock']) }}" class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition shadow-sm">
                    پیشاندانی کاڵا کەمبووەوەکان
                </a>
            </div>
        </div>
    @endif

    <!-- Categories & Brands Quick Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Categories Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-md">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                    <span>🏷️ پۆلێنەکان (Categories)</span>
                    <span class="bg-amber-500/10 text-amber-300 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $categories->count() }}</span>
                </h3>
                <button onclick="openCategoryModal()" class="text-[11px] text-amber-400 hover:text-amber-300 font-bold transition">
                    + زیادکردنی پۆلێن
                </button>
            </div>
            <div class="flex flex-wrap gap-1.5 max-h-28 overflow-y-auto pr-1">
                @forelse($categories as $cat)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs bg-slate-800/80 border border-slate-700/60 text-slate-200 shadow-sm">
                        <span class="font-bold text-amber-300">#</span>
                        <span class="font-semibold">{{ $cat->name_ku }}</span>
                        <span class="text-[10px] text-slate-400">({{ $cat->name_en }})</span>
                    </span>
                @empty
                    <span class="text-xs text-slate-500">هیچ پۆلێنێک تۆمار نەکراوە.</span>
                @endforelse
            </div>
        </div>

        <!-- Brands Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-md">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-xs font-bold text-violet-400 flex items-center gap-1.5">
                    <span>✨ براندە باوەڕپێکراوەکان (Brands)</span>
                    <span class="bg-violet-500/10 text-violet-300 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $brands->count() }}</span>
                </h3>
                <button onclick="openBrandModal()" class="text-[11px] text-violet-400 hover:text-violet-300 font-bold transition">
                    + زیادکردنی براند
                </button>
            </div>
            <div class="flex flex-wrap gap-1.5 max-h-28 overflow-y-auto pr-1">
                @forelse($brands as $brand)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs bg-slate-800/80 border border-slate-700/60 text-slate-200 shadow-sm">
                        <span class="font-bold text-violet-400">★</span>
                        <span class="font-semibold text-white">{{ $brand->name }}</span>
                        @if($brand->country_of_origin)
                            <span class="text-[10px] text-slate-400">({{ $brand->country_of_origin }})</span>
                        @endif
                    </span>
                @empty
                    <span class="text-xs text-slate-500">هیچ براندێک تۆمار نەکراوە.</span>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Products Master Catalog Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="p-4 border-b border-slate-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>📋 کاڵا ناسێنراوەکان و جۆرەکانیان (Products & Variants)</span>
                </h2>
                <span class="text-xs text-slate-500">کۆی گشتی: {{ $products->total() }} کاڵا</span>
            </div>

            <!-- فلتەری خێرا: هەموو / کەمبووەوە / تەواوبوو -->
            <div class="flex items-center gap-1.5 bg-slate-800/80 p-1 rounded-xl border border-slate-700/60 text-xs">
                <a href="{{ route('inventory.index', ['filter' => 'all']) }}" 
                    class="px-3 py-1.5 rounded-lg font-bold transition {{ ($filter ?? 'all') === 'all' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white' }}">
                    هەموو
                </a>
                <a href="{{ route('inventory.index', ['filter' => 'low_stock']) }}" 
                    class="px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1 {{ ($filter ?? '') === 'low_stock' ? 'bg-amber-600 text-white' : 'text-slate-400 hover:text-amber-400' }}">
                    <span>⚠️ کەمبووەوە</span>
                    @if(isset($lowStockCount) && $lowStockCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-500/30 text-amber-200">{{ $lowStockCount }}</span>
                    @endif
                </a>
                <a href="{{ route('inventory.index', ['filter' => 'out_of_stock']) }}" 
                    class="px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1 {{ ($filter ?? '') === 'out_of_stock' ? 'bg-rose-600 text-white' : 'text-slate-400 hover:text-rose-400' }}">
                    <span>🚫 تەواوبوو</span>
                    @if(isset($outOfStockCount) && $outOfStockCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-500/30 text-rose-200">{{ $outOfStockCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-850/70 text-slate-400 border-b border-slate-800">
                        <th class="p-3.5 font-bold">ناوی کاڵا (کوردی / ئینگلیزی)</th>
                        <th class="p-3.5 font-bold">پۆلێن و براند</th>
                        <th class="p-3.5 font-bold">جۆر، تام و قەبارە</th>
                        <th class="p-3.5 font-bold">بارکۆد و SKU</th>
                        <th class="p-3.5 font-bold">تێچووی کڕین</th>
                        <th class="p-3.5 font-bold">نرخی فرۆشتن (ئاسایی / ئەندام)</th>
                        <th class="p-3.5 font-bold">بڕی کۆگا</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($products as $prod)
                        @foreach($prod->variants as $variant)
                            @php
                                $stock = $variant->batches->where('is_blocked', false)->filter(fn($b) => $b->expiry_date->isFuture())->sum('quantity_available');
                            @endphp
                            <tr class="hover:bg-slate-850/40 transition">
                                <td class="p-3.5 font-bold text-white">
                                    {{ $prod->name_ku }}
                                    <span class="text-[11px] text-slate-400 block font-mono">{{ $prod->name_en }}</span>
                                </td>
                                <td class="p-3.5 text-slate-300">
                                    <span class="text-indigo-400 font-semibold block">{{ $prod->category?->name_ku ?? 'گشتی' }}</span>
                                    <span class="text-slate-500 text-[11px]">{{ $prod->brand?->name ?? '-' }}</span>
                                </td>
                                <td class="p-3.5 text-slate-200">
                                    @if($variant->flavor)
                                        <span class="bg-slate-800 px-2 py-0.5 rounded text-[11px] ml-1">🍦 {{ $variant->flavor }}</span>
                                    @endif
                                    @if($variant->size)
                                        <span class="bg-slate-800 px-2 py-0.5 rounded text-[11px]">⚖️ {{ $variant->size }}</span>
                                    @endif
                                </td>
                                <td class="p-3.5 font-mono text-[11px] text-slate-400">
                                    <span class="text-slate-300 block font-bold">{{ $variant->barcode }}</span>
                                    <span>{{ $variant->sku }}</span>
                                </td>
                                <td class="p-3.5 font-mono text-slate-300">
                                    {{ number_format($variant->default_cost_price) }} IQD
                                </td>
                                <td class="p-3.5 font-mono">
                                    <span class="font-bold text-white block">{{ number_format($variant->default_sale_price) }} IQD</span>
                                    @if($variant->member_sale_price)
                                        <span class="text-[11px] text-emerald-400">ئەندام: {{ number_format($variant->member_sale_price) }} IQD</span>
                                    @endif
                                </td>
                                <td class="p-3.5 font-mono">
                                    @php
                                        $alertLimit = $variant->min_stock_alert ?? 5;
                                    @endphp
                                    @if($stock <= 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold text-rose-400 bg-rose-500/10 border border-rose-500/20">
                                            <span>🚫</span>
                                            <span>تەواوبووە (0)</span>
                                        </span>
                                    @elseif($stock <= $alertLimit)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold text-amber-400 bg-amber-500/10 border border-amber-500/20" title="ئاستی ئاگادارکردنەوە: {{ $alertLimit }} دانە">
                                            <span>⚠️</span>
                                            <span>کەمبووەوە ({{ $stock }})</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20">
                                            <span>✓</span>
                                            <span>{{ $stock }} دانە</span>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">
                                هیچ کاڵایەک نەناسێنراوە. کلیک لەسەر "ناساندنی کاڵای نوێ" بکە.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-800">
            {{ $products->links() }}
        </div>
    </div>

    <!-- FEFO Batches & Expiry Dates Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="p-4 border-b border-slate-800 flex justify-between items-center">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span>🗓️ وەجبەکانی کۆگا و بەرواری بەسەرچوون (FEFO Inventory Batches)</span>
            </h2>
            <span class="text-xs text-slate-400">ڕیزکراوە بەپێی نزیکترین بەرواری بەسەرچوون</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-850/70 text-slate-400 border-b border-slate-800">
                        <th class="p-3.5 font-bold">ژمارەی باچ / وەجبە</th>
                        <th class="p-3.5 font-bold">کاڵا و تام</th>
                        <th class="p-3.5 font-bold">دابینکەر</th>
                        <th class="p-3.5 font-bold">بەرواری بەسەرچوون</th>
                        <th class="p-3.5 font-bold">تێچووی کڕین</th>
                        <th class="p-3.5 font-bold">بڕی وەرگیراو</th>
                        <th class="p-3.5 font-bold">بڕی بەردەست</th>
                        <th class="p-3.5 font-bold">دۆخ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($batches as $b)
                        @php
                            $isExpired = $b->expiry_date->isPast();
                            $isNear = !$isExpired && $b->expiry_date->diffInDays(now()) <= 60;
                        @endphp
                        <tr class="hover:bg-slate-850/40 transition">
                            <td class="p-3.5 font-mono font-bold text-white">
                                {{ $b->batch_number }}
                            </td>
                            <td class="p-3.5 font-medium text-slate-200">
                                {{ $b->variant?->product?->name_ku }}
                                <span class="text-[10px] text-slate-400 block font-mono">{{ $b->variant?->flavor }} - {{ $b->variant?->size }}</span>
                            </td>
                            <td class="p-3.5 text-slate-300">
                                {{ $b->supplier?->name ?? 'هاوردەکردنی ڕاستەوخۆ' }}
                            </td>
                            <td class="p-3.5 font-mono font-bold">
                                @if($isExpired)
                                    <span class="text-rose-400 bg-rose-500/10 border border-rose-500/30 px-2 py-0.5 rounded">
                                        {{ $b->expiry_date->format('Y-m-d') }} (بەسەرچووە - بلۆک)
                                    </span>
                                @elseif($isNear)
                                    <span class="text-amber-400 bg-amber-500/10 border border-amber-500/30 px-2 py-0.5 rounded">
                                        {{ $b->expiry_date->format('Y-m-d') }} (نزیکە)
                                    </span>
                                @else
                                    <span class="text-emerald-400 font-normal">
                                        {{ $b->expiry_date->format('Y-m-d') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3.5 font-mono text-slate-300">
                                {{ number_format($b->unit_cost_price) }} IQD
                            </td>
                            <td class="p-3.5 font-mono text-slate-400">
                                {{ $b->quantity_received }}
                            </td>
                            <td class="p-3.5 font-mono font-bold text-white">
                                {{ $b->quantity_available }} دانە
                            </td>
                            <td class="p-3.5">
                                @if($isExpired)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                        بلۆککراوە لە فرۆشتن
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                        چالاکە (FEFO)
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">
                                هیچ وەجبەیەکی کۆگا تۆمار نەکراوە.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal 1: ناساندنی کاڵای نوێ (New Product & Variant Modal) -->
<div id="product-modal" class="fixed inset-0 bg-black/75 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>➕ ناساندنی کاڵای نوێ بە سێ زمان و جۆرەکانی</span>
            </h3>
            <button onclick="closeProductModal()" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <form action="{{ route('inventory.products.store') }}" method="POST" class="space-y-4">
            @csrf
            <!-- ناوی کاڵا بە ٣ زمان -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">ناوی کاڵا بە کوردی *</label>
                    <input type="text" name="name_ku" required placeholder="وەک: گۆڵد ستاندارد وەی" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">الاسم بالعربية *</label>
                    <input type="text" name="name_ar" required placeholder="واي بروتين جولد" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">English Name *</label>
                    <input type="text" name="name_en" required placeholder="Gold Standard Whey" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-mono text-left focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <!-- پۆلێن و براند -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">پۆلێن (Category)</label>
                    <select name="category_id" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-indigo-500">
                        <option value="">-- هەڵبژاردنی پۆلێن --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name_ku }} ({{ $cat->name_en }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">براند (Brand)</label>
                    <select name="brand_id" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-indigo-500">
                        <option value="">-- هەڵبژاردنی براند --</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- جۆری کاڵا: بارکۆد، SKU، تام و قەبارە -->
            <div class="p-4 bg-slate-850/80 rounded-2xl border border-slate-700/60 space-y-3">
                <span class="text-xs font-bold text-indigo-400 block border-b border-slate-750 pb-2">زانیاری تایبەتی جۆر (Variant):</span>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">کۆدی بارکۆد (Barcode) *</label>
                        <input type="text" name="barcode" required placeholder="دەتوانیت بە سکەنەر سکەنی بکەیت..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-mono text-left focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">کۆدی SKU بێهاوتا *</label>
                        <input type="text" name="sku" required placeholder="وەک: ON-WHEY-CHOC-5LB" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-mono text-left focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">تام (Flavor)</label>
                        <input type="text" name="flavor" placeholder="وەک: شوکۆلاتە، ڤانیلا، فراولە..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">قەبارە یان کێش (Size / Weight)</label>
                        <input type="text" name="size" placeholder="وەک: 5 lbs, 2 kg, 60 Servings..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <!-- نرخەکان -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">تێچووی کڕین (Cost) *</label>
                        <input type="number" name="default_cost_price" step="0.01" required placeholder="60000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">نرخی فرۆشتنی ئاسایی *</label>
                        <input type="number" name="default_sale_price" step="0.01" required placeholder="85000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-emerald-400 mb-1">نرخ بۆ ئەندامانی هۆڵ</label>
                        <input type="number" name="member_sale_price" step="0.01" placeholder="80000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-emerald-500">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" onclick="closeProductModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500">
                    ناساندن و پاشەکەوتکردن
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: تۆمارکردنی کڕین و وەجبەی نوێ (Receive Stock Modal) -->
<div id="batch-modal" class="fixed inset-0 bg-black/75 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 space-y-4">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>📥 تۆمارکردنی فاکتۆری کڕین و هاتنی وەجبە (FEFO Batch)</span>
            </h3>
            <button onclick="closeBatchModal()" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <form action="{{ route('inventory.batches.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" name="shop_id" value="{{ $shop?->id }}">

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">کاڵا و جۆری کڕدراو *</label>
                <select name="variant_id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500">
                    <option value="">-- هەڵبژاردنی کاڵا --</option>
                    @foreach($products as $p)
                        @foreach($p->variants as $v)
                            <option value="{{ $v->id }}">
                                {{ $p->name_ku }} - {{ $v->flavor }} ({{ $v->size }}) - بارکۆد: {{ $v->barcode }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">دابینکەر (Supplier)</label>
                <select name="supplier_id" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500">
                    <option value="">-- هەڵبژاردنی دابینکەر --</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->phone }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">ژمارەی باچ / وەجبە (Lot Number) *</label>
                    <input type="text" name="batch_number" required placeholder="وەک: LOT-2026-003" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-mono focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-rose-400 mb-1">بەرواری بەسەرچوون (Expiry Date) *</label>
                    <input type="date" name="expiry_date" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-mono focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">تێچووی کڕینی یەک دانە (IQD) *</label>
                    <input type="number" name="unit_cost_price" step="0.01" required placeholder="60000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">بڕی وەرگیراو (ژمارەی دانە) *</label>
                    <input type="number" name="quantity_received" min="1" required placeholder="20" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ژمارەی فاکتۆری کڕین / وەصڵ</label>
                <input type="text" name="invoice_reference" placeholder="وەک: INV-SUP-887" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" onclick="closeBatchModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500">
                    وەرگرتن و زیادکردن بۆ کۆگا
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: زیادکردنی دابینکەر (New Supplier Modal) -->
<div id="supplier-modal" class="fixed inset-0 bg-black/75 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>🏢 زیادکردنی دابینکەری نوێ</span>
            </h3>
            <button onclick="closeSupplierModal()" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <form action="{{ route('inventory.suppliers.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ناوی کۆمپانیا یان دابینکەر *</label>
                <input type="text" name="name" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-slate-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ژمارەی مۆبایل *</label>
                <input type="text" name="phone" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-slate-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ئیمەیڵ</label>
                <input type="email" name="email" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-slate-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ناونیشان</label>
                <input type="text" name="address" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-slate-500">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-slate-700 hover:bg-slate-600">
                    تۆمارکردن
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 4: زیادکردنی پۆلێن (New Category Modal) -->
<div id="category-modal" class="fixed inset-0 bg-black/75 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>🏷️ زیادکردنی پۆلێنی نوێ (Category)</span>
            </h3>
            <button onclick="closeCategoryModal()" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <form action="{{ route('inventory.categories.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ناوی پۆلێن بە کوردی *</label>
                <input type="text" name="name_ku" required placeholder="وەک: پڕۆتین، کریاتین، ڤیتامین..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-amber-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ناوی پۆلێن بە ئینگلیزی (English)</label>
                <input type="text" name="name_en" placeholder="e.g. Whey Protein, Creatine, Pre-Workout..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-amber-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ناوی پۆلێن بە عەرەبی (العربية)</label>
                <input type="text" name="name_ar" placeholder="مثال: بروتين مصل اللبن، كرياتين..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-amber-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">پۆلێنی سەرەکی (ئارەزوومەندانە)</label>
                <select name="parent_id" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-amber-500">
                    <option value="">-- بەبێ پۆلێنی باوان (پۆلێنی سەرەکی خۆیەتی) --</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name_ku }} ({{ $c->name_en }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 shadow-lg shadow-amber-600/30">
                    تۆمارکردنی پۆلێن
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 5: زیادکردنی براند (New Brand Modal) -->
<div id="brand-modal" class="fixed inset-0 bg-black/75 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>✨ زیادکردنی براندی نوێ (Brand)</span>
            </h3>
            <button onclick="closeBrandModal()" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <form action="{{ route('inventory.brands.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ناوی براند (Brand Name) *</label>
                <input type="text" name="name" required placeholder="وەک: Optimum Nutrition, Dymatize, MuscleTech..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-violet-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">وڵاتی دروستکەر (Country of Origin)</label>
                <input type="text" name="country_of_origin" placeholder="وەک: ئەمریکا (USA), بەریتانیا (UK), ئەڵمانیا..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-violet-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">لینکی لۆگۆ (ئارەزوومەندانە)</label>
                <input type="url" name="logo_url" placeholder="https://..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-violet-500">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" onclick="closeBrandModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-violet-600 hover:bg-violet-500 shadow-lg shadow-violet-600/30">
                    تۆمارکردنی براند
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openProductModal() {
        document.getElementById('product-modal').classList.remove('hidden');
    }
    function closeProductModal() {
        document.getElementById('product-modal').classList.add('hidden');
    }

    function openBatchModal() {
        document.getElementById('batch-modal').classList.remove('hidden');
    }
    function closeBatchModal() {
        document.getElementById('batch-modal').classList.add('hidden');
    }

    function openSupplierModal() {
        document.getElementById('supplier-modal').classList.remove('hidden');
    }
    function closeSupplierModal() {
        document.getElementById('supplier-modal').classList.add('hidden');
    }

    function openCategoryModal() {
        document.getElementById('category-modal').classList.remove('hidden');
    }
    function closeCategoryModal() {
        document.getElementById('category-modal').classList.add('hidden');
    }

    function openBrandModal() {
        document.getElementById('brand-modal').classList.remove('hidden');
    }
    function closeBrandModal() {
        document.getElementById('brand-modal').classList.add('hidden');
    }
</script>
@endpush
