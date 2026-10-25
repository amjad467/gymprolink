<!DOCTYPE html>
<html lang="ku" dir="rtl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پشکنینی دووقۆناغی (2FA Verification) | GymPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full space-y-6">

        <div class="text-center space-y-2">
            <div class="w-16 h-16 mx-auto rounded-3xl bg-gradient-to-tr from-amber-500 to-emerald-400 flex items-center justify-center text-3xl shadow-xl shadow-amber-500/20">
                🔐
            </div>
            <h1 class="text-2xl font-black text-white">پشکنینی ئاسایشی دووقۆناغی (2FA)</h1>
            <p class="text-xs text-slate-400">
                بەکارهێنەر: <span class="text-emerald-400 font-bold">{{ $user->name }}</span>
            </p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-5">
            <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-2xl text-xs text-amber-300">
                🛡️ ئەم هەژمارە پارێزراوە بە پشکنینی دووقۆناغی. تکایە پینی نهێنی ٦ ژمارەیی بنووسە بۆ تەواوکردنی چوونەژوورەوە.
            </div>

            @if($errors->any())
                <div class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-xs text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.2fa.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-2 text-center">
                        پینی نهێنی (6-Digit Security PIN) *
                    </label>
                    <input type="password" 
                           name="pin" 
                           maxlength="6" 
                           required 
                           autofocus
                           placeholder="••••••" 
                           class="w-full bg-slate-800/90 border border-slate-700 focus:border-amber-500 rounded-2xl px-4 py-3 text-center text-2xl tracking-[0.5em] text-white placeholder-slate-600 focus:outline-none font-mono transition">
                </div>

                <button type="submit" 
                        class="w-full bg-gradient-to-r from-amber-600 to-emerald-500 hover:from-amber-500 hover:to-emerald-400 text-white font-bold py-3 rounded-2xl text-xs transition shadow-lg shadow-amber-600/30 flex items-center justify-center gap-2">
                    <span>🔓 سەلماندن و چوونەژوورەوە</span>
                </button>
            </form>

            <div class="text-center pt-2">
                <a href="{{ route('login') }}" class="text-xs text-slate-400 hover:text-white transition">
                    ← گەڕانەوە بۆ لاپەڕەی چوونەژوورەوە
                </a>
            </div>
        </div>

        <div class="text-center text-xs text-slate-500">
            GymPro Multi-Gym Security Shield &copy; 2026
        </div>
    </div>
</body>
</html>
