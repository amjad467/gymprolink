@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>🚪 کۆنسۆڵی دەرگای زیرەک و سکەنەر (Smart Access Control Gate)</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                تاقیکردنەوەی ڕاستەوخۆی سکەنکردنی QR Code و پشکنینی مۆڵەتی چوونەژوورەوە لە هۆڵی {{ $activeGym->name }}
            </p>
        <div class="flex items-center gap-3">
            <button type="button" onclick="connectArduinoSerial()" id="gate-btn-arduino" class="px-3 py-1.5 rounded-xl bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-300 border border-cyan-500/30 text-xs font-bold transition flex items-center gap-1.5">
                <span>🔌</span>
                <span id="gate-arduino-text">پەیوەستکردنی ئاردینۆ (USB Serial)</span>
            </button>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                ئامێر ئۆنلاینە (Device Online)
            </span>
        </div>
    </div>

    <!-- Gate Interactive Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Turnstile Gate Display Screen (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
            <div id="gate-screen" class="bg-slate-900 border-2 border-slate-700 rounded-3xl p-6 text-center shadow-2xl transition-all duration-300 min-h-[420px] flex flex-col justify-between items-center relative overflow-hidden">
                <!-- Device Header -->
                <div class="w-full flex justify-between items-center text-xs text-slate-400 border-b border-slate-800 pb-3">
                    <span id="screen-device-name" class="font-bold text-white">GATE_CENTRAL_01</span>
                    <span class="font-mono text-[11px] text-emerald-400">STATUS: READY</span>
                </div>

                <!-- Center Animation / Result Area -->
                <div id="screen-content" class="my-auto py-8 text-center">
                    <div id="screen-icon" class="w-24 h-24 rounded-full bg-slate-800 border-2 border-slate-700 mx-auto flex items-center justify-center text-4xl mb-4 transition">
                        🔒
                    </div>
                    <h3 id="screen-title" class="text-xl font-black text-white mb-1">
                        چاوەڕوانی سکەنکردنە...
                    </h3>
                    <p id="screen-message" class="text-xs text-slate-400 max-w-xs mx-auto">
                        تکایە کارتی ئەندامێتی یان کۆدی QR لەسەر شاشەی مۆبایل پێشکەش بە سکەنەرەکە بکە
                    </p>
                    <div id="screen-member-info" class="hidden mt-4 p-3 bg-slate-800/80 rounded-xl text-xs border border-slate-700/60 max-w-xs mx-auto">
                        <span id="member-name" class="font-bold text-emerald-400 block text-sm"></span>
                        <span id="member-plan" class="text-slate-300 block text-[11px] mt-0.5"></span>
                        <span id="member-expiry" class="text-slate-400 block text-[10px] mt-1 font-mono"></span>
                    </div>
                </div>

                <!-- Footer Relay State -->
                <div class="w-full pt-3 border-t border-slate-800 flex justify-between items-center text-xs">
                    <span class="text-slate-500">پەیوەندی بە Relay:</span>
                    <span id="relay-status" class="px-2.5 py-0.5 rounded font-mono font-bold bg-slate-800 text-slate-400">
                        LOCKED
                    </span>
                </div>
            </div>

            <!-- Scanner Barcode Input Form -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg">
                <form id="scan-form" class="space-y-3">
                    <label class="block text-xs font-bold text-slate-300">
                        خوێندنەوەی دەستی / سکەنەری فیزیکی:
                    </label>
                    <div class="flex gap-2">
                        <input type="text" id="manual-code" placeholder="کۆدی سکەنکراو لێرە دەنووسرێت..." class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 font-mono text-left" autofocus>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-2 rounded-xl text-sm transition">
                            سکەن
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Testing Scenarios & Members List (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
            <!-- Fast Test Scenarios -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
                <h3 class="text-sm font-bold text-white mb-3 flex items-center gap-2">
                    <span>⚡ تاقیکردنەوەی خێرای حاڵەتەکان بە یەک کلیک (One-Click Scenarios):</span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($members as $m)
                        @php
                            $cred = $m->primaryCredential;
                            $statusColor = match($m->status) {
                                'active' => 'emerald',
                                'expired' => 'rose',
                                'frozen' => 'amber',
                                default => 'slate'
                            };
                        @endphp
                        <button onclick="testScan('{{ $cred?->raw_token }}')" class="text-right p-3 rounded-xl border border-{{ $statusColor }}-500/30 bg-{{ $statusColor }}-500/10 hover:bg-{{ $statusColor }}-500/20 transition flex items-center justify-between group">
                            <div>
                                <span class="font-bold text-sm text-white block">{{ $m->full_name }}</span>
                                <span class="text-[11px] text-slate-400">
                                    پلان: {{ $m->activeMembership?->plan?->name ?? 'بەسەرچوو' }}
                                </span>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded font-bold uppercase bg-{{ $statusColor }}-500/20 text-{{ $statusColor }}-300">
                                {{ $m->status }}
                            </span>
                        </button>
                    @endforeach

                    <!-- ئەندامی هۆڵێکی تر (Cross-gym) -->
                    @if($otherGymMember)
                        <button onclick="testScan('{{ $otherGymMember->primaryCredential?->raw_token }}')" class="text-right p-3 rounded-xl border border-purple-500/30 bg-purple-500/10 hover:bg-purple-500/20 transition flex items-center justify-between">
                            <div>
                                <span class="font-bold text-sm text-white block">{{ $otherGymMember->full_name }}</span>
                                <span class="text-[11px] text-purple-300">
                                    ئەندامی {{ $otherGymMember->gym?->name }}
                                </span>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded font-bold uppercase bg-purple-500/20 text-purple-300">
                                هۆڵی تر
                            </span>
                        </button>
                    @endif

                    <!-- کۆدی ساختە -->
                    <button onclick="testScan('fake_invalid_token.signature_123')" class="text-right p-3 rounded-xl border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 transition flex items-center justify-between">
                        <div>
                            <span class="font-bold text-sm text-white block">کۆدی ساختە / دەستکاریکراو</span>
                            <span class="text-[11px] text-red-300">واژۆی هەڵە</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded font-bold uppercase bg-red-500/20 text-red-300">
                            ساختە
                        </span>
                    </button>
                </div>
            </div>

            <!-- Live Scan Audit Log -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
                <h3 class="text-sm font-bold text-white mb-3 flex items-center justify-between">
                    <span>📋 تۆماری چوونەژوورەوەی ڕاستەوخۆ (Real-time Gate Events)</span>
                    <span class="text-xs text-slate-500 font-normal">نوێترین ١٥ سکەن</span>
                </h3>

                <div class="overflow-y-auto max-h-[300px]">
                    <table class="w-full text-right text-xs">
                        <tbody id="events-log-body" class="divide-y divide-slate-800/70">
                            @foreach($events as $ev)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-2.5 font-bold text-white">
                                        {{ $ev->member?->full_name ?? 'نەناسراو' }}
                                    </td>
                                    <td class="py-2.5">
                                        @if($ev->status === 'GRANTED')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                ✓ ڕێگەپێدراوە
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                ✕ ڕەتکرایەوە: {{ $ev->denial_reason }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-slate-500 text-[11px] font-mono">
                                        {{ $ev->scanned_at ? $ev->scanned_at->format('H:i:s') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    const deviceId = "{{ $devices->first()?->id }}";

    function testScan(token) {
        if (!token) {
            alert('ئەم ئەندامە هیچ تۆکنێکی QR ی نییە.');
            return;
        }
        document.getElementById('manual-code').value = token;
        submitScan(token);
    }

    document.getElementById('scan-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const code = document.getElementById('manual-code').value.trim();
        if (code) {
            submitScan(code);
        }
    });

    async function submitScan(scannedCode) {
        const screen = document.getElementById('gate-screen');
        const icon = document.getElementById('screen-icon');
        const title = document.getElementById('screen-title');
        const message = document.getElementById('screen-message');
        const memberInfo = document.getElementById('screen-member-info');
        const memberName = document.getElementById('member-name');
        const memberPlan = document.getElementById('member-plan');
        const memberExpiry = document.getElementById('member-expiry');
        const relayStatus = document.getElementById('relay-status');

        // Loading state
        icon.innerText = '⏳';
        title.innerText = 'پشکنین...';
        message.innerText = 'داواکاری دەنێردرێت بۆ Backend بۆ پشکنینی ٨ مەرجەکە...';
        screen.className = "bg-slate-900 border-2 border-amber-500/50 rounded-3xl p-6 text-center shadow-2xl transition-all duration-300 min-h-[420px] flex flex-col justify-between items-center relative overflow-hidden";

        try {
            const response = await fetch("{{ route('gate.simulator.scan') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    device_id: deviceId,
                    scanned_code: scannedCode
                })
            });

            const data = await response.json();

            if (data.access === true) {
                // سەرکەوتوو (GRANTED)
                screen.className = "bg-gradient-to-b from-slate-900 to-emerald-950 border-2 border-emerald-500 rounded-3xl p-6 text-center shadow-2xl shadow-emerald-500/20 transition-all duration-300 min-h-[420px] flex flex-col justify-between items-center relative overflow-hidden";
                icon.className = "w-24 h-24 rounded-full bg-emerald-500/20 border-2 border-emerald-400 mx-auto flex items-center justify-center text-4xl mb-4";
                icon.innerText = '🔓';
                title.innerText = data.message;
                title.className = "text-xl font-black text-emerald-300 mb-1";
                message.innerText = "دەرگا کرایەوە بۆ ماوەی " + (data.device.relay_pulse_ms / 1000) + " چرکە.";

                if (data.member) {
                    memberInfo.classList.remove('hidden');
                    memberName.innerText = data.member.name + " (" + data.member.code + ")";
                    memberPlan.innerText = "پلان: " + (data.member.plan_name || 'ئاسایی');
                    memberExpiry.innerText = "کۆتایی بەشداری: " + data.member.expires_at;
                }

                relayStatus.innerText = "UNLOCKED (" + (data.device.relay_pulse_ms / 1000) + "s)";
                relayStatus.className = "px-2.5 py-0.5 rounded font-mono font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 animate-pulse";

                // ناردنی سیگناڵ بۆ ئاردینۆ لە ڕێگەی Web Serial
                sendArduinoPulse();
                speakVoice("بەخێربێیت بەڕێز " + (data.member ? data.member.name : ''));

                // پاش ٣ چرکە دەگەڕێتەوە دۆخی بنەڕەتی
                setTimeout(() => {
                    relayStatus.innerText = "LOCKED";
                    relayStatus.className = "px-2.5 py-0.5 rounded font-mono font-bold bg-slate-800 text-slate-400";
                }, data.device.relay_pulse_ms);

            } else {
                // ڕەتکرایەوە (DENIED)
                screen.className = "bg-gradient-to-b from-slate-900 to-rose-950 border-2 border-rose-500 rounded-3xl p-6 text-center shadow-2xl shadow-rose-500/20 transition-all duration-300 min-h-[420px] flex flex-col justify-between items-center relative overflow-hidden";
                icon.className = "w-24 h-24 rounded-full bg-rose-500/20 border-2 border-rose-400 mx-auto flex items-center justify-center text-4xl mb-4";
                icon.innerText = '⛔';
                title.innerText = 'ڕێگەپێدان ڕەتکرایەوە!';
                title.className = "text-xl font-black text-rose-300 mb-1";
                message.innerText = data.message + " (" + (data.reason_code || 'DENIED') + ")";
                memberInfo.classList.add('hidden');

                relayStatus.innerText = "LOCKED (DENIED)";
                relayStatus.className = "px-2.5 py-0.5 rounded font-mono font-bold bg-rose-500/20 text-rose-400 border border-rose-500/40";

                let denyVoice = "تۆمار نەکراوە";
                if (data.reason_code === 'membership_expired') denyVoice = "ماوەی بەشداربوونەت تەواو بووە";
                else if (data.message) denyVoice = data.message;
                speakVoice(denyVoice);
            }

            // زیادکردنی تۆمارەکە بۆ لاگی ڕاستەوخۆ لە خوارەوە
            const tableBody = document.getElementById('events-log-body');
            const row = document.createElement('tr');
            row.className = "bg-slate-800/40 animate-pulse";
            row.innerHTML = `
                <td class="py-2.5 font-bold text-white">${data.member ? data.member.name : 'نەناسراو'}</td>
                <td class="py-2.5">
                    ${data.access ? 
                        '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">✓ ڕێگەپێدراوە</span>' : 
                        '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">✕ ' + data.message + '</span>'
                    }
                </td>
                <td class="py-2.5 text-slate-500 text-[11px] font-mono">ئێستا</td>
            `;
            tableBody.insertBefore(row, tableBody.firstChild);

        } catch (err) {
            console.error(err);
            alert('هەڵەیەک لە پەیوەندی بە سێرڤەر ڕوویدا.');
        }
    }

    let webSerialPort = null;
    let webSerialWriter = null;

    async function connectArduinoSerial() {
        if (!('serial' in navigator)) {
            alert('تکایە براوسەری Chrome یان Edge بەکاربهێنە بۆ پەیوەندی Web Serial.');
            return;
        }
        try {
            webSerialPort = await navigator.serial.requestPort();
            await webSerialPort.open({ baudRate: 9600 });
            webSerialWriter = webSerialPort.writable.getWriter();
            document.getElementById('gate-arduino-text').innerText = 'ئاردینۆ بەستراوە (USB 9600)';
            document.getElementById('gate-btn-arduino').className = 'px-3 py-1.5 rounded-xl bg-emerald-600/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold transition flex items-center gap-1.5';
            speakVoice('ئاردینۆ پەیوەست کرا');
        } catch (e) {
            alert('هەڵە لە پەیوەندی: ' + e.message);
        }
    }

    async function sendArduinoPulse() {
        if (webSerialWriter) {
            try {
                await webSerialWriter.write(new TextEncoder().encode("1\n"));
            } catch (e) {
                console.error(e);
            }
        }
    }

    function speakVoice(text) {
        if ('speechSynthesis' in window) {
            try {
                window.speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance(text);
                utter.rate = 0.95;
                utter.lang = 'ar-SA';
                window.speechSynthesis.speak(utter);
            } catch (e) {}
        }
    }
</script>
@endpush
