<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>چوونەژوورەوە | GymPro Smart Management</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        slate: {
                            850: '#151f32',
                            950: '#070d18',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 relative overflow-x-hidden selection:bg-emerald-500 selection:text-white">

    <!-- Ambient background glow effects -->
    <div class="fixed top-[-10%] right-[-5%] w-[450px] h-[450px] bg-emerald-600/15 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="fixed bottom-[-10%] left-[-5%] w-[450px] h-[450px] bg-teal-600/10 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-md my-8 relative z-10 space-y-6">

        <!-- Logo & Header -->
        <div class="text-center space-y-2">
            <div class="w-16 h-16 rounded-3xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-black text-2xl text-white shadow-2xl shadow-emerald-500/30 mx-auto">
                GP
            </div>
            <h1 class="text-2xl font-black bg-gradient-to-r from-emerald-400 via-teal-200 to-white bg-clip-text text-transparent">
                GymPro
            </h1>
            <p class="text-xs text-slate-400">
                سیستەمی پێشکەوتووی بەڕێوەبردنی هۆڵی وەرزشی و دەرگای زیرەک
            </p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-5">
            <div class="border-b border-slate-800 pb-3">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <span>🔐 چوونەژوورەوە بۆ هەژمارەکەت</span>
                </h2>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    ئیمەیڵ یان ژمارەی مۆبایل و وشەی نهێنی بنووسە
                </p>
            </div>

            <!-- Alerts -->
            @if(session('info'))
                <div class="p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs">
                    {{ session('info') }}
                </div>
            @endif

            @if(session('error'))
                <div class="p-3 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="p-3 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                    @foreach($errors->all() as $error)
                        <p>⚠️ {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Quick Click to Fill Admin Credentials Banner -->
            <div onclick="fillLogin('admin', 'password123')" class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 hover:bg-emerald-500/20 cursor-pointer transition flex items-center justify-between text-xs group">
                <div class="flex items-center gap-2">
                    <span class="text-base">👑</span>
                    <div>
                        <span class="font-black text-emerald-400 block">چوونەژوورەوەی ئەدمینی سەرەکی (تەنها کلیک بکە):</span>
                        <span class="text-[11px] text-slate-300 font-mono">ئیمەیڵ: <strong class="text-white">admin</strong> | وشەی نهێنی: <strong class="text-white">password123</strong></span>
                    </div>
                </div>
                <span class="text-emerald-400 font-bold group-hover:translate-x-[-3px] transition-transform text-sm">←</span>
            </div>

            <!-- Login Form -->
            <form action="{{ url('/login') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Login Identifier (Email or Phone or Username) -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">
                        ئیمەیڵ، ژمارەی مۆبایل، یان ناوی بەکارهێنەر *
                    </label>
                    <div class="relative">
                        <input type="text" 
                               name="login" 
                               value="{{ old('login', 'admin') }}" 
                               required 
                               autocapitalize="none"
                               autocorrect="off"
                               spellcheck="false"
                               placeholder="admin یان admin@gympro.com یان 0750xxxxxxx" 
                               class="w-full bg-slate-800/90 border border-slate-700 focus:border-emerald-500 rounded-2xl px-4 py-3 sm:py-2.5 text-base sm:text-xs text-white placeholder-slate-500 focus:outline-none transition font-sans">
                        <span class="absolute left-3 top-3.5 sm:top-2.5 text-slate-500 text-sm pointer-events-none">📧</span>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="text-xs font-bold text-slate-300">
                            وشەی نهێنی (Password) *
                        </label>
                    </div>
                    <div class="relative">
                        <input type="password" 
                               id="password-input" 
                               name="password" 
                               value="password123"
                               required 
                               placeholder="••••••••" 
                               class="w-full bg-slate-800/90 border border-slate-700 focus:border-emerald-500 rounded-2xl px-4 py-3 sm:py-2.5 text-base sm:text-xs text-white placeholder-slate-500 focus:outline-none transition font-sans">
                        <button type="button" 
                                onclick="togglePasswordVisibility()" 
                                class="absolute left-3 top-3.5 sm:top-2.5 text-slate-400 hover:text-white text-xs transition p-1">
                            👁️
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 text-slate-300 cursor-pointer select-none">
                        <input type="checkbox" name="remember" checked class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-emerald-500 focus:ring-0">
                        <span>لەبیرم مەچۆ (Remember Me)</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold py-3.5 sm:py-3 rounded-2xl text-sm sm:text-xs transition shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 active:scale-[0.98]">
                    <span>🚀 چوونەژوورەوە بۆ سیستەم</span>
                </button>
            </form>

            <!-- Quick Demo Login Buttons (چوونەژوورەوەی خێرا بەپێی ڕۆڵەکان) -->
            <div class="pt-4 border-t border-slate-800 space-y-2.5">
                <span class="text-[11px] font-bold text-slate-400 block text-center">
                    ⚡ تاقیکردنەوەی خێرا بەپێی ڕۆڵەکان (1-Click Demo Logins):
                </span>
                
                <div class="grid grid-cols-2 gap-2 text-[11px]">
                    <button type="button" 
                            onclick="fillLogin('admin', 'password123')" 
                            class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700 hover:border-slate-600 transition text-right">
                        <span class="font-bold text-emerald-400 block">👑 Super Admin</span>
                        <span class="text-[10px] text-slate-400">دەسەڵاتی گشتی</span>
                    </button>

                    <button type="button" 
                            onclick="fillLogin('owner1@gympro.test', 'password123')" 
                            class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700 hover:border-slate-600 transition text-right">
                        <span class="font-bold text-teal-400 block">🏛️ خاوەنی هۆڵ</span>
                        <span class="text-[10px] text-slate-400">دەسەڵاتی هۆڵ</span>
                    </button>

                    <button type="button" 
                            onclick="fillLogin('manager1@gympro.test', 'password123')" 
                            class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700 hover:border-slate-600 transition text-right">
                        <span class="font-bold text-indigo-400 block">👔 بەڕێوەبەر</span>
                        <span class="text-[10px] text-slate-400">ئەندام و بەشداری</span>
                    </button>

                    <button type="button" 
                            onclick="fillLogin('accountant1@gympro.test', 'password123')" 
                            class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700 hover:border-slate-600 transition text-right">
                        <span class="font-bold text-amber-400 block">💰 ژمێریار</span>
                        <span class="text-[10px] text-slate-400">دارایی و خەرجی</span>
                    </button>

                    <button type="button" 
                            onclick="fillLogin('trainer1@gympro.test', 'password123')" 
                            class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700 hover:border-slate-600 transition text-right">
                        <span class="font-bold text-rose-400 block">🏋️‍♂️ ڕاهێنەر</span>
                        <span class="text-[10px] text-slate-400">مەشق و یاریزان</span>
                    </button>

                    <button type="button" 
                            onclick="fillLogin('cashier1@gympro.test', 'password123')" 
                            class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700 hover:border-slate-600 transition text-right">
                        <span class="font-bold text-cyan-400 block">🛍️ کاشێری شۆپ</span>
                        <span class="text-[10px] text-slate-400">خاڵی فرۆشتن POS</span>
                    </button>
                </div>
            </div>

        <!-- Install GymPro Mobile App Banner (PWA) -->
        <div id="pwa-install-container" class="block">
            <button type="button" 
                    id="pwa-install-btn" 
                    onclick="handleAppInstallClick()"
                    class="w-full bg-gradient-to-r from-emerald-600/30 via-slate-900 to-teal-600/20 hover:from-emerald-600/40 hover:to-teal-600/30 border border-emerald-500/50 text-white font-bold p-3.5 rounded-2xl text-xs transition flex items-center justify-between shadow-xl shadow-emerald-950/50 group">
                <div class="flex items-center gap-3 text-right">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-xl shrink-0">
                        📲
                    </div>
                    <div>
                        <span class="font-black text-white block text-xs">دابەزاندنی GymPro بۆ سەر شاشەی مۆبایل</span>
                        <span class="text-[11px] text-emerald-300">ئایکۆنی ئەپ دادەنێت و بەبێ وێبگەڕ دەکرێتەوە</span>
                    </div>
                </div>
                <span class="bg-emerald-500 group-hover:bg-emerald-400 text-slate-950 px-3 py-1.5 rounded-xl text-xs font-black shrink-0 transition shadow">
                    داگرتن ⬇
                </span>
            </button>
        </div>

        <div class="text-center text-xs text-slate-500">
            GymPro Multi-Gym Management & Smart Access Control Platform &copy; {{ date('Y') }}
        </div>
    </div>

    <!-- PWA Installation Guide Modal for iPhone and Android -->
    <div id="pwa-guide-modal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-end sm:items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700/80 rounded-3xl max-w-sm w-full p-6 space-y-4 shadow-2xl relative text-right">
            <button type="button" onclick="closePwaModal()" class="absolute left-4 top-4 text-slate-400 hover:text-white text-lg font-bold w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center">
                &times;
            </button>
            <div class="flex items-center gap-3 border-b border-slate-800 pb-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-black text-white text-lg shadow-lg">
                    GP
                </div>
                <div>
                    <h3 class="font-bold text-white text-sm">چۆنیەتی دابەزاندنی ئەپی GymPro</h3>
                    <p class="text-[11px] text-emerald-400">هەنگاو بە هەنگاو بۆ سەر شاشەی مۆبایل</p>
                </div>
            </div>

            <!-- Steps -->
            <div class="space-y-3 text-xs text-slate-300">
                <div class="p-3 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-1">
                    <div class="font-bold text-white flex items-center gap-1.5">
                        <span class="text-emerald-400">١.</span>
                        <span>ئەگەر ئایفۆنە (Safari):</span>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        لە خوارەوەی وێبگەڕی سەفاری پەنجە بنێ بە دوگمەی <strong class="text-white">هاوبەشکردن (Share 📤)</strong>، پاشان کەمێک بڕۆ خوارەوە و کلیک بکە لەسەر <strong class="text-emerald-400">Add to Home Screen ➕</strong>.
                    </p>
                </div>

                <div class="p-3 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-1">
                    <div class="font-bold text-white flex items-center gap-1.5">
                        <span class="text-teal-400">٢.</span>
                        <span>ئەگەر ئەندرۆیدە (Chrome):</span>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        لە سەرەوە دەستە ڕاست پەنجە بنێ بە سێ خاڵەکە <strong class="text-white">`⋮`</strong>، پاشان هەڵبژێرە <strong class="text-emerald-400">Install app</strong> یان <strong class="text-emerald-400">Add to Home screen</strong>.
                    </p>
                </div>
            </div>

            <button type="button" onclick="closePwaModal()" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 rounded-2xl text-xs transition">
                تێگەیشتم (داخستن)
            </button>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password-input');
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        function fillLogin(login, pass) {
            document.querySelector('input[name="login"]').value = login;
            document.getElementById('password-input').value = pass;
        }

        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }

        // PWA Install Prompt Handler
        let deferredPrompt = null;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        function handleAppInstallClick() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        const container = document.getElementById('pwa-install-container');
                        if (container) container.classList.add('hidden');
                    }
                    deferredPrompt = null;
                });
            } else {
                // Open visual step-by-step guide
                document.getElementById('pwa-guide-modal').classList.remove('hidden');
            }
        }

        function closePwaModal() {
            document.getElementById('pwa-guide-modal').classList.add('hidden');
        }

        // Check if already in standalone app mode
        window.addEventListener('DOMContentLoaded', () => {
            const isStandalone = window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;
            if (isStandalone) {
                const container = document.getElementById('pwa-install-container');
                if (container) container.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
