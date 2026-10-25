@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center font-black text-2xl">
                ⚙️
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white">
                    ڕێکخستنی سیستەم، هۆڵ، فۆنت و دیزاین
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    گۆڕینی ناوی هۆڵ، بارکردنی لۆگۆ و وێنە، دیاریکردنی جۆر و قەبارەی فۆنت، مۆدی شەو و ڕۆژ، و زمانەکانی کوردی و عەرەبی
                </p>
            </div>
        </div>
    </div>

    <!-- Main Settings Form -->
    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- 1. Gym Identity & Contact Info -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <h2 class="text-base font-black text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                <span>🏛️</span>
                <span>زانیارییە سەرەکییەکانی هۆڵ و سیستەم</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">ناوی هۆڵ (Gym Name):</label>
                    <input type="text" name="name" id="gym-name-input" oninput="updateLivePreview()" value="{{ old('name', $gym->name) }}" required class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500 font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">دروشم یان ژێرنووس (Slogan / Subtitle):</label>
                    <input type="text" name="slogan" id="gym-slogan-input" oninput="updateLivePreview()" value="{{ old('slogan', $settings['slogan'] ?? '') }}" placeholder="بۆ نموونە: باشترین هۆڵی وەرزشی و ڕاهێنان" class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">ژمارەی پەیوەندی / مۆبایل (Phone):</label>
                    <input type="text" name="phone" value="{{ old('phone', $gym->phone) }}" placeholder="0770 000 0000" class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">ئیمەیڵی فەرمی (Email):</label>
                    <input type="email" name="email" value="{{ old('email', $gym->email) }}" placeholder="info@gympro.test" class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-300 mb-1">ناونیشان و شوێن (Location / Address):</label>
                    <input type="text" name="address" value="{{ old('address', $gym->address) }}" placeholder="سلێمانی - شەقامی سەرەکی" class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
                </div>
            </div>
        </div>

        <!-- 2. Logo & Media Upload -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <h2 class="text-base font-black text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                <span>🎨</span>
                <span>لۆگۆ و ناسنامەی بینراو (Logo & Avatar)</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
                <!-- Current Logo Preview -->
                <div class="sm:col-span-4 text-center space-y-2">
                    <span class="text-xs text-slate-400 font-bold block">لۆگۆی هەنووکەیی:</span>
                    <div class="w-24 h-24 rounded-2xl bg-slate-800 border-2 border-dashed border-slate-700 flex items-center justify-center mx-auto overflow-hidden shadow-inner p-1">
                        @if($gym->logo_url)
                            <img id="logo-preview-img" src="{{ $gym->logo_url }}" alt="Gym Logo" class="w-full h-full object-cover rounded-xl">
                        @else
                            <div id="logo-preview-placeholder" class="w-full h-full bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-black text-2xl text-white rounded-xl">
                                GP
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Upload New File or Select Preset -->
                <div class="sm:col-span-8 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">بارکردنی وێنە / لۆگۆی نوێ لە کۆمپیوتەرەکەت:</label>
                        <input type="file" name="logo_file" accept="image/*" onchange="previewSelectedLogo(this)" class="w-full bg-slate-800 border border-slate-700 rounded-2xl p-2 text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500 cursor-pointer">
                        <span class="text-[10px] text-slate-500 mt-1 block">پشتگیری فۆرماتەکانی PNG, JPG, WEBP, SVG دەکات (تا 4MB).</span>
                    </div>

                    <!-- Preset Badges -->
                    <div>
                        <span class="text-xs font-bold text-slate-400 block mb-1.5">یان یەکێک لە لۆگۆ ئامادەکراوەکان هەڵبژێرە:</span>
                        <div class="flex items-center gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="logo_preset" value="/uploads/logos/preset_1.png" class="hidden">
                                <span class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white font-black text-sm border-2 border-transparent hover:border-emerald-400 block">
                                    🏋️
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="logo_preset" value="/uploads/logos/preset_2.png" class="hidden">
                                <span class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-black text-sm border-2 border-transparent hover:border-indigo-400 block">
                                    💪
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="logo_preset" value="/uploads/logos/preset_3.png" class="hidden">
                                <span class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-600 to-rose-600 flex items-center justify-center text-white font-black text-sm border-2 border-transparent hover:border-amber-400 block">
                                    🏆
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Typography & Font Customization (فۆنت و قەبارە) -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <h2 class="text-base font-black text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                <span>🔤</span>
                <span>فۆنت، قەبارە، زمان و مۆدی بینین</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Font Family -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">جۆری فۆنت (Font Family):</label>
                    <select name="font_family" id="font-family-select" onchange="updateLivePreview()" class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold">
                        <option value="Vazirmatn" {{ $currentFont === 'Vazirmatn' ? 'selected' : '' }}>
                            فۆنتی وەزیر (Vazirmatn) - مۆدێرن و خوێنەرەوەی باڵا (پێشنیارکراو)
                        </option>
                        <option value="Noto Kufi Arabic" {{ $currentFont === 'Noto Kufi Arabic' ? 'selected' : '' }}>
                            فۆنتی کوفی (Noto Kufi Arabic) - هەیبەتدار و فەرمی
                        </option>
                        <option value="Tahoma" {{ $currentFont === 'Tahoma' ? 'selected' : '' }}>
                            تاهۆما (Tahoma) - ستاندارد و خاوێن
                        </option>
                        <option value="Inter" {{ $currentFont === 'Inter' ? 'selected' : '' }}>
                            ئینتەر (Inter System) - گونجاو بۆ داتا و ژمارەکان
                        </option>
                    </select>
                </div>

                <!-- Font Size Scaling -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">قەبارەی نووسین لە سیستەم (Font Scale):</label>
                    <select name="font_size" id="font-size-select" onchange="updateLivePreview()" class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold">
                        <option value="small" {{ $currentFontSize === 'small' ? 'selected' : '' }}>بچووک (Small - 90%)</option>
                        <option value="normal" {{ $currentFontSize === 'normal' ? 'selected' : '' }}>ئاسایی و ستاندارد (Normal - 100%)</option>
                        <option value="large" {{ $currentFontSize === 'large' ? 'selected' : '' }}>گەورە و دیار (Large - 110%)</option>
                        <option value="xlarge" {{ $currentFontSize === 'xlarge' ? 'selected' : '' }}>زۆر گەورە (Extra Large - 120%)</option>
                    </select>
                </div>

                <!-- Theme (Dark / Light) -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">مۆدی دیزاین (Theme Mode):</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="p-3 rounded-2xl border border-slate-700 bg-slate-800 flex items-center justify-center gap-2 cursor-pointer font-bold text-xs text-white">
                            <input type="radio" name="theme" value="dark" {{ $currentTheme === 'dark' ? 'checked' : '' }}>
                            <span>🌙 مۆدی تاریک (Dark)</span>
                        </label>
                        <label class="p-3 rounded-2xl border border-slate-700 bg-slate-800 flex items-center justify-center gap-2 cursor-pointer font-bold text-xs text-slate-300">
                            <input type="radio" name="theme" value="light" {{ $currentTheme === 'light' ? 'checked' : '' }}>
                            <span>☀️ مۆدی ڕۆژ (Light)</span>
                        </label>
                    </div>
                </div>

                <!-- Language (Kurdish / Arabic) -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">زمانی بنەڕەتی سیستەم (System Language):</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="p-3 rounded-2xl border border-slate-700 bg-slate-800 flex items-center justify-center gap-2 cursor-pointer font-bold text-xs text-white">
                            <input type="radio" name="language" value="ku" {{ $currentLang === 'ku' ? 'checked' : '' }}>
                            <span>☀️ زمانی کوردی (سۆرانی)</span>
                        </label>
                        <label class="p-3 rounded-2xl border border-slate-700 bg-slate-800 flex items-center justify-center gap-2 cursor-pointer font-bold text-xs text-slate-300">
                            <input type="radio" name="language" value="ar" {{ $currentLang === 'ar' ? 'checked' : '' }}>
                            <span>🌐 اللغة العربية</span>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Live Preview Card (شاشەی پێشبینینی ڕاستەوخۆ) -->
            <div class="mt-4 p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                <span class="text-[11px] font-bold text-emerald-400 block">👀 شاشەی پێشبینینی ڕاستەوخۆی فۆنت و دیزاین (Live Preview):</span>
                
                <div id="live-preview-box" class="p-4 rounded-xl bg-slate-900 border border-slate-700 transition-all">
                    <h3 id="preview-title" class="font-black text-white text-base">
                        {{ $gym->name }} - {{ $settings['slogan'] ?? 'سیستەمی هۆڵ و دەرگای زیرەک' }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">
                        بەخێربێن بۆ سیستەمی GymPro! ئەم نموونەیەیە چۆنیەتی دەرکەوتنی پیتە کوردی و عەرەبییەکان پیشان دەدات.
                    </p>
                    <p class="text-xs text-slate-500 font-mono mt-1">
                        أهلاً بكم في نظام إدارة الأندية الرياضية الذكية - GymPro Cloud Platform.
                    </p>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('dashboard') }}" class="px-5 py-3 rounded-2xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700 transition">
                پەشیمانبوونەوە
            </a>
            <button type="submit" class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-xs sm:text-sm px-6 py-3 rounded-2xl shadow-xl shadow-emerald-600/30 transition flex items-center gap-2 hover:scale-[1.02]">
                <span>✓ پاشەکەوتکردنی گۆڕانکارییەکان</span>
            </button>
        </div>

    </form>

</div>

@push('scripts')
<script>
    function updateLivePreview() {
        const font = document.getElementById('font-family-select').value;
        const size = document.getElementById('font-size-select').value;
        const name = document.getElementById('gym-name-input').value || 'GymPro';
        const slogan = document.getElementById('gym-slogan-input').value || 'سیستەمی هۆڵ و دەرگای زیرەک';

        const previewBox = document.getElementById('live-preview-box');
        const previewTitle = document.getElementById('preview-title');

        previewTitle.textContent = `${name} - ${slogan}`;
        previewBox.style.fontFamily = font + ', sans-serif';

        const scaleMap = {
            'small': '0.9',
            'normal': '1.0',
            'large': '1.1',
            'xlarge': '1.2'
        };
        previewBox.style.fontSize = scaleMap[size] + 'rem';
    }

    function previewSelectedLogo(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                let img = document.getElementById('logo-preview-img');
                const placeholder = document.getElementById('logo-preview-placeholder');
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                if (!img) {
                    img = document.createElement('img');
                    img.id = 'logo-preview-img';
                    img.className = 'w-full h-full object-cover rounded-xl';
                    input.parentElement.parentElement.previousElementSibling.querySelector('div').appendChild(img);
                }
                img.src = e.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
@endsection
