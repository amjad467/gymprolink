<!DOCTYPE html>
@php
    $activeGymId = \App\Scopes\GymScope::getActiveGymId() ?? \App\Models\Gym::first()?->id;
    $currentGym = $activeGym ?? \App\Models\Gym::find($activeGymId);
    $gymSettings = $currentGym?->settings ?? [];
    
    $appTheme = session('app_theme', $gymSettings['theme'] ?? 'dark');
    $appFont = session('app_font', $gymSettings['font_family'] ?? 'Vazirmatn');
    $appFontSize = session('app_font_size', $gymSettings['font_size'] ?? 'normal');
    $appLocale = session('app_locale', $gymSettings['language'] ?? 'ku');

    $fontFamilyCss = match($appFont) {
        'Noto Kufi Arabic' => "'Noto Kufi Arabic', sans-serif",
        'Tahoma' => "Tahoma, 'Vazirmatn', sans-serif",
        'Inter' => "Inter, 'Vazirmatn', sans-serif",
        default => "'Vazirmatn', sans-serif",
    };

    $fontSizeScale = match($appFontSize) {
        'small' => '0.92',
        'large' => '1.08',
        'xlarge' => '1.16',
        default => '1.0',
    };
@endphp
<html lang="{{ $appLocale === 'ar' ? 'ar' : 'ckb' }}" dir="rtl" class="{{ $appTheme === 'light' ? 'light-mode' : 'dark-mode' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ($currentGym->name ?? 'GymPro Smart Management') }}</title>
    
    <!-- PWA & Mobile Web App Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#059669">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="GymPro">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">

    <!-- Google Fonts: Vazirmatn & Noto Kufi Arabic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;600;700;800;900&family=Vazirmatn:wght@300;400;500;600;700;800;900&family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['{{ $appFont }}', 'Vazirmatn', 'sans-serif'],
                    },
                    colors: {
                        gym: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            900: '#064e3b',
                            dark: '#0f172a',
                            card: '#1e293b',
                            border: '#334155',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        :root {
            --app-font: {{ $fontFamilyCss }};
            --app-scale: {{ $fontSizeScale }};
        }
        body {
            font-family: var(--app-font);
            font-size: calc(1rem * var(--app-scale));
        }

        /* Light Mode Styling Overrides */
        html.light-mode body {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
        html.light-mode header,
        html.light-mode nav,
        html.light-mode footer {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            color: #0f172a !important;
        }
        html.light-mode .bg-slate-950 {
            background-color: #f8fafc !important;
        }
        html.light-mode .bg-slate-900 {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            color: #0f172a !important;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
        }
        html.light-mode .bg-slate-800 {
            background-color: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            color: #1e293b !important;
        }
        html.light-mode .border-slate-800,
        html.light-mode .border-slate-700 {
            border-color: #e2e8f0 !important;
        }
        html.light-mode .text-white {
            color: #0f172a !important;
        }
        html.light-mode .text-slate-400,
        html.light-mode .text-slate-300 {
            color: #475569 !important;
        }
        html.light-mode .divide-slate-800 > :not([hidden]) ~ :not([hidden]),
        html.light-mode .divide-slate-700 > :not([hidden]) ~ :not([hidden]) {
            border-color: #e2e8f0 !important;
        }

        /* Mobile Touch & Viewport Responsiveness */
        @media (max-width: 640px) {
            input, select, textarea {
                font-size: 16px !important; /* Prevents iOS Safari auto-zoom */
            }
        }
        .overflow-x-auto, .overflow-y-auto {
            -webkit-overflow-scrolling: touch;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col antialiased transition-colors duration-200">

    <!-- Top Navigation Bar -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40 transition-colors">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2">
            
            <!-- Left: Logo & Gym Name -->
            <div class="flex items-center gap-3">
                <button type="button" onclick="openMegaMenu()" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition flex items-center gap-1.5 shadow-sm" title="کردنەوەی لیست مینو">
                    <span class="text-base font-black">☰</span>
                    <span class="text-xs font-bold hidden md:inline">{{ $appLocale === 'ar' ? 'القائمة الشاملة' : 'لیست مینو' }}</span>
                </button>

                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    @if($currentGym && $currentGym->logo_url)
                        <img src="{{ $currentGym->logo_url }}" alt="{{ $currentGym->name }}" class="w-10 h-10 rounded-xl object-cover border border-slate-700 shadow-md">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-black text-xl text-white shadow-lg shadow-emerald-500/20">
                            GP
                        </div>
                    @endif
                    <div>
                        <span class="text-base sm:text-lg font-black bg-gradient-to-r from-emerald-400 to-teal-200 bg-clip-text text-transparent block leading-tight">
                            {{ $currentGym->name ?? 'GymPro' }}
                        </span>
                        <span class="text-[10px] sm:text-xs text-emerald-400 font-semibold hidden sm:block">
                            {{ $gymSettings['slogan'] ?? ($appLocale === 'ar' ? 'نظام النوادي وبوابات الدخول الذكية' : 'سیستەمی هۆڵ و دەرگای زیرەک') }}
                        </span>
                    </div>
                </a>
            </div>

            <!-- Right: Controls (Gym Switcher, Theme Switcher, Lang, Status) -->
            <div class="flex items-center gap-2 sm:gap-3">
                
                <!-- Gym Selector -->
                <form action="{{ route('dashboard') }}" method="GET" class="hidden sm:flex items-center">
                    <select name="switch_gym" id="switch_gym" onchange="this.form.submit()" class="bg-slate-800 text-emerald-400 border border-slate-700 text-xs sm:text-sm rounded-xl px-2.5 py-1.5 focus:ring-emerald-500 font-medium cursor-pointer">
                        @foreach(\App\Models\Gym::all() as $g)
                            <option value="{{ $g->id }}" {{ ($activeGymId == $g->id) ? 'selected' : '' }}>
                                🏛️ {{ $g->name }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <!-- Language Switcher (Kurdish / Arabic) -->
                <div class="flex items-center bg-slate-800 border border-slate-700 rounded-xl p-0.5 text-xs font-bold">
                    <a href="{{ route('settings.locale', 'ku') }}" class="px-2 py-1 rounded-lg transition {{ $appLocale === 'ku' ? 'bg-emerald-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
                        کوردی
                    </a>
                    <a href="{{ route('settings.locale', 'ar') }}" class="px-2 py-1 rounded-lg transition {{ $appLocale === 'ar' ? 'bg-emerald-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
                        عربي
                    </a>
                </div>

                <!-- Dark / Light Theme Toggle Button -->
                <button type="button" onclick="toggleThemeMode()" id="theme-toggle-btn" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700 transition" title="گۆڕینی مۆدی شەو و ڕۆژ">
                    <span id="theme-icon">{{ $appTheme === 'light' ? '☀️' : '🌙' }}</span>
                </button>

                <!-- Settings Shortcut -->
                <a href="{{ route('settings.index') }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition" title="ڕێکخستنەکان">
                    ⚙️
                </a>

                <!-- Backups Shortcut -->
                <a href="{{ route('backups.index') }}" class="hidden md:flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 text-xs font-bold transition" title="باک ئەپی سیستەم">
                    <span>💾</span>
                    <span>{{ $appLocale === 'ar' ? 'النسخ الاحتياطي' : 'باک ئەپ' }}</span>
                </a>

                <!-- Users & Auth Profile -->
                @auth
                    <div class="flex items-center gap-2 bg-slate-800/90 border border-slate-700 rounded-2xl px-2.5 py-1 shadow-sm">
                        <div class="hidden sm:flex flex-col text-right leading-none">
                            <span class="text-xs font-bold text-white">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] font-semibold text-emerald-400 mt-0.5">
                                @if(auth()->user()->is_super_admin)
                                    👑 سوپەر ئەدمین
                                @else
                                    {{ auth()->user()->primary_role->display_name_ku ?? 'بەکارهێنەر' }}
                                @endif
                            </span>
                        </div>
                        <a href="{{ route('users.index') }}" class="p-1.5 rounded-xl bg-slate-700/60 hover:bg-purple-600/30 text-purple-300 transition" title="بەکارهێنەران و دەسەڵاتەکان">
                            👥
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="inline m-0">
                            @csrf
                            <button type="submit" class="p-1.5 rounded-xl bg-slate-700/60 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 transition" title="دەرچوون">
                                🚪
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>

        <!-- Navigation Tabs Bar -->
        <nav class="bg-slate-900/80 border-t border-slate-800/80 px-2 sm:px-4">
            <div class="max-w-7xl mx-auto flex items-center gap-1 sm:gap-2 overflow-x-auto py-2 text-xs sm:text-sm font-bold no-scrollbar">
                <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('dashboard') ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    📊 {{ $appLocale === 'ar' ? 'الرئيسية' : 'داشبۆردی سەرەکی' }}
                </a>
                <a href="{{ route('analytics.sales') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('analytics.*') ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    📈 {{ $appLocale === 'ar' ? 'تحليلات المبيعات والمشتريات' : 'ئاماری کڕین و فرۆشتن' }}
                </a>
                <a href="{{ route('attendance.index') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('attendance.*') || request()->routeIs('gate.*') ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    🚪 {{ $appLocale === 'ar' ? 'الحضور والبوابة' : 'هاتنی ڕۆژانە و دەرگا' }}
                </a>
                <a href="{{ route('pos.index') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('pos.*') ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    🛍️ {{ $appLocale === 'ar' ? 'نقطة البيع POS' : 'شۆپی تەواوکەر و POS' }}
                </a>
                <a href="{{ route('inventory.index') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('inventory.*') ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    📦 {{ $appLocale === 'ar' ? 'المخزن والمشتريات' : 'کۆگا و کڕین لە دابینکەر' }}
                </a>
                <a href="{{ route('members.index') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('members.*') ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    👥 {{ $appLocale === 'ar' ? 'المشتركون والخطط' : 'یاریزانان و بەشداری' }}
                </a>
                <a href="{{ route('debts.index') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('debts.*') ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    💳 {{ $appLocale === 'ar' ? 'الديون والمستحقات' : 'قەرزەکان و کەشف حیساب' }}
                </a>
                <a href="{{ route('expenses.index') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('expenses.*') ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    💸 {{ $appLocale === 'ar' ? 'المصاريف والنفقات' : 'خەرجییەکان' }}
                </a>
                <a href="{{ route('financial.closing') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('financial.*') ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    📑 {{ $appLocale === 'ar' ? 'إغلاق الصندوق (Z-Report)' : 'داخستنی سندوق (Z-Report)' }}
                </a>
                <a href="{{ route('users.index') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('users.*') ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    🔑 {{ $appLocale === 'ar' ? 'المستخدمون والصلاحيات' : 'بەکارهێنەران و مۆڵەتەکان' }}
                </a>
                <a href="{{ route('audit-logs.index') }}" class="px-3 py-1.5 rounded-xl transition flex-shrink-0 {{ request()->routeIs('audit-logs.*') ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    🛡️ {{ $appLocale === 'ar' ? 'سجل الرقابة والأمان' : 'تۆماری چاودێری (Audit)' }}
                </a>
            </div>
        </nav>
    </header>

    <!-- Mega Menu Modal (لیست مینو بۆ زۆربەی بەشەکان) -->
    <div id="mega-menu-modal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden flex items-start justify-center p-3 sm:p-6 overflow-y-auto">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-4xl w-full p-6 shadow-2xl mt-4 sm:mt-8 space-y-6 animate-in fade-in zoom-in-95 duration-200 relative">
            
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📋</span>
                    <div>
                        <h2 class="text-xl font-black text-white">
                            {{ $appLocale === 'ar' ? 'القائمة الشاملة للنظام' : 'پێڕستی سەرەکی و بەشەکانی سیستەم (Mega Menu)' }}
                        </h2>
                        <p class="text-xs text-slate-400">
                            {{ $appLocale === 'ar' ? 'وصول سريع ومباشر لكافة أقسام وإعدادات النادي' : 'دەستگەیشتنی خێرا بە سەرجەم بەشەکان، ئامارەکان و ڕێکخستنەکان' }}
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeMegaMenu()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center font-bold text-sm transition">
                    &times;
                </button>
            </div>

            <!-- Mega Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                
                <!-- 1. داشبۆرد و شیکاری -->
                <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2.5">
                    <span class="font-black text-sm text-emerald-400 flex items-center gap-1.5">
                        <span>📊</span>
                        <span>{{ $appLocale === 'ar' ? 'لوحات القيادة والتحليل' : 'داشبۆرد و شیکاری دارایی' }}</span>
                    </span>
                    <ul class="space-y-1.5">
                        <li>
                            <a href="{{ route('dashboard') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-slate-200 font-bold">
                                📊 {{ $appLocale === 'ar' ? 'لوحة التحكم الرئيسية' : 'داشبۆردی سەرەکی هۆڵ' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('analytics.sales') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-emerald-300 font-bold">
                                📈 {{ $appLocale === 'ar' ? 'شاشة تحليل المبيعات والمشتريات' : 'شیکاری کڕین و فرۆشتن و قازانج' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('expenses.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-amber-300 font-bold">
                                💸 {{ $appLocale === 'ar' ? 'إدارة المصاريف والنفقات' : 'خەرجییەکان (کرێ، کارەبا، مووچە...)' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('financial.closing') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-teal-300 font-bold">
                                📑 {{ $appLocale === 'ar' ? 'إغلاق الصندوق اليومي (Z-Report)' : 'داخستنی سندوق (Z-Report)' }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- 2. هاتنی ڕۆژانە و دەرگای زیرەک -->
                <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2.5">
                    <span class="font-black text-sm text-teal-400 flex items-center gap-1.5">
                        <span>🚪</span>
                        <span>{{ $appLocale === 'ar' ? 'الحضور والأجهزة' : 'هاتنی ڕۆژانە و دەرگا' }}</span>
                    </span>
                    <ul class="space-y-1.5">
                        <li>
                            <a href="{{ route('attendance.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-slate-200 font-bold">
                                🚪 {{ $appLocale === 'ar' ? 'سجل الحضور اليومي' : 'هاتن و چوونی یاریزانان' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('gate.simulator') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-teal-300 font-bold">
                                ⚡ {{ $appLocale === 'ar' ? 'محاكي واختبار البوابة الذكية' : 'سیمولەیتەری دەرگای زیرەک' }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- 3. یاریزانان و بەشدارییەکان -->
                <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2.5">
                    <span class="font-black text-sm text-indigo-400 flex items-center gap-1.5">
                        <span>👥</span>
                        <span>{{ $appLocale === 'ar' ? 'إدارة المشتركين' : 'یاریزانان و بەشداری' }}</span>
                    </span>
                    <ul class="space-y-1.5">
                        <li>
                            <a href="{{ route('members.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-slate-200 font-bold">
                                👥 {{ $appLocale === 'ar' ? 'قائمة المشتركين' : 'لیستی سەرجەم یاریزانان' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('members.index') }}?status=expired" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-rose-300 font-bold">
                                🗄️ {{ $appLocale === 'ar' ? 'أرشيف المنتهية اشتراكاتهم (15+ يوم)' : 'ئەرشیفی بەسەرچووەکان (15+ ڕۆژ)' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('debts.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-amber-300 font-bold">
                                💳 {{ $appLocale === 'ar' ? 'سجل الديون والمستحقات' : 'قەرزەکان و کەشف حیساب' }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- 4. شۆپی تەواوکەر و POS -->
                <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2.5">
                    <span class="font-black text-sm text-amber-400 flex items-center gap-1.5">
                        <span>🛍️</span>
                        <span>{{ $appLocale === 'ar' ? 'نقطة البيع والمكملات' : 'شۆپی تەواوکەر و POS' }}</span>
                    </span>
                    <ul class="space-y-1.5">
                        <li>
                            <a href="{{ route('pos.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-slate-200 font-bold">
                                🛍️ {{ $appLocale === 'ar' ? 'شاشة البيع المباشر (POS)' : 'فرۆشتنی ڕاستەوخۆ (POS)' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('pos.invoices.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-emerald-400 font-bold">
                                🧾 {{ $appLocale === 'ar' ? 'سجل الفواتير وطباعة الوصولات' : 'لیستی فرۆشتنەکان و چاپی پسووڵە' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('inventory.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-amber-300 font-bold">
                                📦 {{ $appLocale === 'ar' ? 'المخزن، الباتشات والموردين' : 'کۆگا، وەجبەکان و دابینکەران' }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- 5. ڕێکخستن و دیزاین -->
                <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2.5">
                    <span class="font-black text-sm text-blue-400 flex items-center gap-1.5">
                        <span>⚙️</span>
                        <span>{{ $appLocale === 'ar' ? 'الإعدادات واللغات' : 'ڕێکخستن و فۆنت' }}</span>
                    </span>
                    <ul class="space-y-1.5">
                        <li>
                            <a href="{{ route('settings.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-slate-200 font-bold">
                                ⚙️ {{ $appLocale === 'ar' ? 'تخصيص الاسم، الشعار، الخطوط' : 'ناوی هۆڵ، لۆگۆ، فۆنت و سایز' }}
                            </a>
                        </li>
                        <li>
                            <button type="button" onclick="toggleThemeMode(); closeMegaMenu();" class="w-full text-right p-2 rounded-xl hover:bg-slate-700/60 transition text-amber-300 font-bold">
                                🌗 {{ $appLocale === 'ar' ? 'تبديل الوضع (ليلي / نهاري)' : 'گۆڕینی مۆد (شەو / ڕۆژ)' }}
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- 6. باک ئەپ و سکیوریتی -->
                <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2.5">
                    <span class="font-black text-sm text-rose-400 flex items-center gap-1.5">
                        <span>💾</span>
                        <span>{{ $appLocale === 'ar' ? 'الأمان والنسخ الاحتياطي' : 'باک ئەپ و پاراستنی داتا' }}</span>
                    </span>
                    <ul class="space-y-1.5">
                        <li>
                            <a href="{{ route('backups.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-rose-300 font-bold">
                                💾 {{ $appLocale === 'ar' ? 'مدير النسخ الاحتياطية للنظام' : 'باک ئەپی تەواوەتی داتابەیس' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('users.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-purple-300 font-bold">
                                🔑 {{ $appLocale === 'ar' ? 'المستخدمون والصلاحيات' : 'بەکارهێنەران و مۆڵەتەکان' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('audit-logs.index') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-amber-300 font-bold">
                                🛡️ {{ $appLocale === 'ar' ? 'سجل الرقابة والعمليات' : 'تۆماری چاودێری کردارەکان (Audit)' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('members.expired.export') }}" class="block p-2 rounded-xl hover:bg-slate-700/60 transition text-slate-300 font-bold">
                                📥 {{ $appLocale === 'ar' ? 'تصدير المشتركين المنتهيين (Excel)' : 'هەناردەی یاریزانانی بەسەرچوو (CSV)' }}
                            </a>
                        </li>
                        <li id="app-install-menu-item">
                            <button type="button" onclick="triggerPwaInstall()" class="w-full text-right block p-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 transition text-emerald-300 font-bold">
                                📲 {{ $appLocale === 'ar' ? 'تثبيت التطبيق على الهاتف' : 'داگرتنی ئەپڵیکەیشن بۆ سەر مۆبایل (PWA)' }}
                            </button>
                        </li>
                    </ul>
                </div>

            </div>

            <!-- Footer note -->
            <div class="pt-3 border-t border-slate-800 flex justify-between items-center text-[11px] text-slate-400">
                <span>GymPro Enterprise Multi-Tenant Smart Gym Platform &copy; 2026</span>
                <button type="button" onclick="closeMegaMenu()" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold">
                    {{ $appLocale === 'ar' ? 'إغلاق' : 'داخستن' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-2.5 sm:px-6 lg:px-8 py-4 sm:py-6 pb-28 sm:pb-8">
        @if(session('success'))
            <div class="mb-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-2xl flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2">
                    <span class="text-lg">✓</span>
                    <span class="font-bold text-xs sm:text-sm">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-rose-500/10 border border-rose-500/30 text-rose-300 px-4 py-3 rounded-2xl flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2">
                    <span class="text-lg">✕</span>
                    <span class="font-bold text-xs sm:text-sm">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Mobile Bottom Navigation Bar (تایبەت بە بەکارهێنەرانی مۆبایل) -->
    <nav class="sm:hidden fixed bottom-0 inset-x-0 bg-slate-900/95 backdrop-blur-xl border-t border-slate-800 z-40 py-2 px-2 shadow-2xl transition-colors">
        <div class="flex items-center justify-around text-center">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl transition {{ request()->routeIs('dashboard') ? 'text-emerald-400 font-black' : 'text-slate-400 hover:text-slate-200' }}">
                <span class="text-xl">📊</span>
                <span class="text-[10px] leading-none">{{ $appLocale === 'ar' ? 'الرئيسية' : 'سەرەکی' }}</span>
            </a>
            <a href="{{ route('attendance.index') }}" class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl transition {{ request()->routeIs('attendance.*') || request()->routeIs('gate.*') ? 'text-emerald-400 font-black' : 'text-slate-400 hover:text-slate-200' }}">
                <span class="text-xl">🚪</span>
                <span class="text-[10px] leading-none">{{ $appLocale === 'ar' ? 'الدخول' : 'دەرگا' }}</span>
            </a>
            <a href="{{ route('pos.index') }}" class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl transition {{ request()->routeIs('pos.*') ? 'text-emerald-400 font-black' : 'text-slate-400 hover:text-slate-200' }}">
                <span class="text-xl">🛍️</span>
                <span class="text-[10px] leading-none">{{ $appLocale === 'ar' ? 'نقطة البيع' : 'شۆپ POS' }}</span>
            </a>
            <a href="{{ route('members.index') }}" class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl transition {{ request()->routeIs('members.*') ? 'text-emerald-400 font-black' : 'text-slate-400 hover:text-slate-200' }}">
                <span class="text-xl">👥</span>
                <span class="text-[10px] leading-none">{{ $appLocale === 'ar' ? 'الأعضاء' : 'ئەندامان' }}</span>
            </a>
            <button type="button" onclick="openMegaMenu()" class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl text-slate-400 hover:text-slate-200 transition">
                <span class="text-xl">📋</span>
                <span class="text-[10px] leading-none">{{ $appLocale === 'ar' ? 'المزيد' : 'پێڕست' }}</span>
            </button>
        </div>
    </nav>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 py-4 mb-16 sm:mb-0 text-center text-xs text-slate-500 transition-colors">
        {{ $currentGym->name ?? 'GymPro' }} &copy; 2026 - {{ $appLocale === 'ar' ? 'النظام المتكامل لإدارة النوادي الرياضية والمبيعات' : 'سیستەمی پیشەیی فرە-هۆڵ، شۆپی تەواوکەر و دەرگای زیرەک' }}
    </footer>

    <script>
        function openMegaMenu() {
            document.getElementById('mega-menu-modal').classList.remove('hidden');
        }

        function closeMegaMenu() {
            document.getElementById('mega-menu-modal').classList.add('hidden');
        }

        // Close on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMegaMenu();
            }
        });

        // Theme Toggle
        function toggleThemeMode() {
            const html = document.documentElement;
            const isLight = html.classList.contains('light-mode');
            const newTheme = isLight ? 'dark' : 'light';

            if (newTheme === 'light') {
                html.classList.remove('dark-mode');
                html.classList.add('light-mode');
                document.getElementById('theme-icon').textContent = '☀️';
            } else {
                html.classList.remove('light-mode');
                html.classList.add('dark-mode');
                document.getElementById('theme-icon').textContent = '🌙';
            }

            localStorage.setItem('gympro_theme', newTheme);

            // Persist to server
            fetch("{{ route('settings.theme.toggle') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).catch(() => {});
        }

        // On load verify localStorage theme
        (function() {
            const savedTheme = localStorage.getItem('gympro_theme');
            if (savedTheme === 'light') {
                document.documentElement.classList.remove('dark-mode');
                document.documentElement.classList.add('light-mode');
                const ic = document.getElementById('theme-icon');
                if (ic) ic.textContent = '☀️';
            }
        })();

        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }

        let pwaDeferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            pwaDeferredPrompt = e;
        });

        function triggerPwaInstall() {
            if (pwaDeferredPrompt) {
                pwaDeferredPrompt.prompt();
                pwaDeferredPrompt.userChoice.then(() => {
                    pwaDeferredPrompt = null;
                });
            } else {
                alert('بۆ داگرتن لە مۆبایل (iOS Safari / Chrome):\nلە وێبگەڕی مۆبایلەکەدا پەنجە بنێ بە دوگمەی Share پاشان (Add to Home Screen / زیادکردن بۆ پەڕەی سەرەکی) هەڵبژێرە.');
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
