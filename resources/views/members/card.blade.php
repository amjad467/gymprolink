<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کارتی ئەندامێتی - {{ $member->full_name }} (#{{ $member->member_code }})</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .card-container { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col items-center justify-center p-4">

    <!-- Action Toolbar (Hidden in Print) -->
    <div class="no-print mb-6 flex items-center gap-3">
        <button onclick="window.print()" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold rounded-2xl text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-2 cursor-pointer transition">
            <span>🖨️</span>
            <span>چاپکردنی کارت (Print Card)</span>
        </button>
        <button onclick="window.close()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-2xl text-xs transition">
            داخستن
        </button>
    </div>

    <!-- Double-Sided Physical ID Card Container (CR80 Standard Card Size: 85.6mm x 53.98mm scaled) -->
    <div class="flex flex-col md:flex-row gap-6 items-center">

        <!-- FRONT SIDE -->
        <div class="card-container w-96 h-56 rounded-3xl bg-gradient-to-br from-slate-900 via-slate-850 to-slate-900 border border-slate-700 p-5 shadow-2xl relative overflow-hidden flex flex-col justify-between">
            <!-- Background Watermark Accent -->
            <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute right-0 top-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-xl pointer-events-none"></div>

            <!-- Header -->
            <div class="flex justify-between items-center relative z-10 border-b border-slate-700/60 pb-2.5">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center font-black text-white text-sm shadow-md">
                        GP
                    </div>
                    <div>
                        <div class="text-xs font-black text-white tracking-wide">{{ $member->gym->name ?? 'GymPro Club' }}</div>
                        <div class="text-[9px] text-emerald-400 font-bold">MEMBER PASS • ناسنامەی ئەندام</div>
                    </div>
                </div>
                <div class="text-[10px] font-mono font-bold bg-slate-800 text-slate-300 px-2 py-0.5 rounded-lg border border-slate-700">
                    #{{ $member->member_code }}
                </div>
            </div>

            <!-- Body Details -->
            <div class="flex items-center gap-4 relative z-10 my-auto">
                <!-- Avatar -->
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-slate-800 to-slate-700 border-2 border-emerald-500/40 flex items-center justify-center text-3xl font-black text-white shadow-inner flex-shrink-0">
                    {{ mb_substr($member->full_name, 0, 1) }}
                </div>

                <!-- Info -->
                <div class="space-y-1 leading-tight flex-1">
                    <div class="text-sm font-black text-white leading-tight truncate">{{ $member->full_name }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">📱 {{ $member->phone }}</div>
                    <div class="text-[10px] text-emerald-400 font-bold">
                        پلانی: {{ $member->activeMembership?->plan?->name ?? 'بەشداری ئاسایی' }}
                    </div>
                    @if($member->activeMembership)
                        <div class="text-[9px] text-amber-300 font-mono">
                            بەسەرچوون: {{ $member->activeMembership->end_date->format('Y-m-d') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Footer Strip -->
            <div class="flex justify-between items-center relative z-10 text-[9px] text-slate-400 border-t border-slate-700/60 pt-2">
                <span>VALID SMART ACCESS PASS</span>
                <span class="font-bold text-white">GYMPRO SMART GATEWAY</span>
            </div>
        </div>

        <!-- BACK SIDE (Secure QR Code & Anti-Passback Barcode) -->
        <div class="card-container w-96 h-56 rounded-3xl bg-slate-900 border border-slate-700 p-5 shadow-2xl relative overflow-hidden flex flex-col justify-between text-center">
            
            <div class="text-xs font-bold text-slate-300 border-b border-slate-700/60 pb-2 flex justify-between items-center">
                <span>سکانکردن بۆ چوونەژوورەوە</span>
                <span class="text-[10px] text-emerald-400 font-mono">HMAC-SHA256 SECURED</span>
            </div>

            <div class="flex items-center justify-around my-auto">
                <!-- QR Code representation for Gate Scanner -->
                <div class="p-2 bg-white rounded-2xl shadow-lg border-2 border-emerald-500">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=95x95&data={{ urlencode($member->primaryCredential?->raw_token ?? $member->member_code) }}" 
                         alt="Gate Access QR" 
                         class="w-20 h-20 rounded-lg">
                </div>

                <!-- Instructions & Barcode -->
                <div class="text-right space-y-1.5 max-w-[180px]">
                    <div class="text-[10px] font-bold text-white">ڕێنمایی بەکارهێنان:</div>
                    <p class="text-[9px] text-slate-400 leading-tight">
                        ئەم کۆدە تەنها بۆ یەک کەسە. سیستەم بە شێوازی Anti-Passback ڕێگری لە بەکارهێنانی دووبارە دەکات.
                    </p>
                    <div class="font-mono text-[9px] bg-slate-800 text-slate-300 px-2 py-1 rounded border border-slate-700">
                        RFID: {{ $member->member_code }}
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-700/60 pt-2 text-[9px] text-slate-500 flex justify-between">
                <span>هێڵی فریاکەوتن: {{ $member->emergency_contact_phone ?? $member->gym->phone ?? '—' }}</span>
                <span>GymPro &copy; 2026</span>
            </div>
        </div>

    </div>

</body>
</html>
