@extends('layouts.app')

@section('content')
<div class="space-y-8 max-w-7xl mx-auto px-2 sm:px-4">

    <!-- 1. Header Bar: Gym Info, Live Clock & Arduino Connection Badge -->
    <div class="relative overflow-hidden bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 shadow-2xl">
        <div class="absolute -top-24 -left-24 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        سیستەمی دەرگای زیرەک و کۆنترۆڵی هاتن
                    </span>
                    <span class="text-xs text-slate-400 font-mono hidden sm:inline">•</span>
                    <span class="text-xs text-slate-400 font-bold hidden sm:inline">{{ $activeGym->name }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white flex items-center gap-2">
                    <span>🚪 داشبۆردی هاتن و ڕۆشتنی ڕۆژانەی یاریزانان</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">
                    لێدانی دەستبەجێی بارکۆد، پشکنینی ڕاستەوخۆی کارایی بەشداری، خوێندنەوەی دەنگی بەخێرهاتن و کردنەوەی ئۆتۆماتیکی دەرگای ئاردینۆ
                </p>
            </div>

            <!-- Live Digital Clock & Hardware Connectivity -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Clock -->
                <div class="px-4 py-2 rounded-2xl bg-slate-800/80 border border-slate-700/60 shadow-inner flex items-center gap-2.5">
                    <span class="text-lg">🕒</span>
                    <div>
                        <div id="live-digital-clock" class="text-sm font-black text-white font-mono tracking-wider">
                            {{ now()->format('h:i:s A') }}
                        </div>
                        <div class="text-[10px] text-slate-400 font-medium">
                            {{ now()->format('Y-m-d') }} (ئەمڕۆ)
                        </div>
                    </div>
                </div>

                <!-- Arduino Status Badge -->
                <div id="arduino-connection-indicator" class="px-3.5 py-2 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center gap-2 text-xs">
                    <span id="arduino-dot" class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-pulse"></span>
                    <div>
                        <span class="text-[10px] text-slate-400 block leading-tight">ئاردینۆ مێگا (COM)</span>
                        <span id="arduino-status-label" class="font-bold text-slate-200 text-xs font-mono">
                            {{ config('arduino.port', 'COM3') }}
                        </span>
                    </div>
                </div>

                <!-- Sound Toggle -->
                <button type="button" onclick="toggleAudio()" id="btn-toggle-sound" class="p-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition" title="دەنگ چالاکە / ناچالاکە">
                    <span id="sound-icon" class="text-base">🔊</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Hero KPI Stats Row (زانیاری خێرا: هاتوو، دەرچوو، ئێستا لە هۆڵدان، تێکڕای کاتی مانەوە) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        
        <!-- Card 1: سەرجەم سەردانەکانی ئەمڕۆ -->
        <a href="{{ route('attendance.index', ['tab' => 'entries']) }}" 
           class="group relative overflow-hidden bg-gradient-to-br from-slate-900 to-indigo-950/40 border {{ $tab === 'entries' ? 'border-indigo-500 ring-2 ring-indigo-500/30' : 'border-slate-800 hover:border-indigo-500/50' }} rounded-3xl p-5 shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black text-indigo-300/90 group-hover:text-indigo-200 transition">
                    📥 سەرجەم هاتووەکانی ئەمڕۆ
                </span>
                <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center text-lg shadow-inner">
                    📥
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span id="kpi-total-entries" class="text-3xl sm:text-4xl font-black text-white tracking-tight font-mono">
                    {{ $totalEntriesToday }}
                </span>
                <span class="text-xs text-slate-400 font-bold">سەردان</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2 flex items-center justify-between">
                <span>سەرجەم تۆمارەکانی هاتن</span>
                <span class="text-indigo-400 font-bold group-hover:translate-x-[-4px] transition-transform">←</span>
            </p>
        </a>

        <!-- Card 2: ئێستا لە هۆڵدان (ئامادەبوو بە شێوەی زیندوو) -->
        <a href="{{ route('attendance.index', ['tab' => 'inside']) }}" 
           class="group relative overflow-hidden bg-gradient-to-br from-slate-900 to-emerald-950/40 border {{ $tab === 'inside' ? 'border-emerald-500 ring-2 ring-emerald-500/30' : 'border-slate-800 hover:border-emerald-500/50' }} rounded-3xl p-5 shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black text-emerald-400 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>🟢 ئێستا لە هۆڵدان</span>
                </span>
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center text-lg shadow-inner">
                    🏋️‍♂️
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span id="kpi-currently-inside" class="text-3xl sm:text-4xl font-black text-emerald-400 tracking-tight font-mono">
                    {{ $currentlyInsideCount }}
                </span>
                <span class="text-xs text-slate-400 font-bold">یاریزان لە ژوورەوەن</span>
            </div>
            <p class="text-[11px] text-emerald-300/80 mt-2 flex items-center justify-between">
                <span>ڕاهێنانی زیندوو</span>
                <span class="text-emerald-400 font-bold group-hover:translate-x-[-4px] transition-transform">←</span>
            </p>
        </a>

        <!-- Card 3: دەرچووەکانی ئەمڕۆ -->
        <a href="{{ route('attendance.index', ['tab' => 'exits']) }}" 
           class="group relative overflow-hidden bg-gradient-to-br from-slate-900 to-amber-950/40 border {{ $tab === 'exits' ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-slate-800 hover:border-amber-500/50' }} rounded-3xl p-5 shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black text-amber-300/90 group-hover:text-amber-200 transition">
                    📤 دەرچووەکانی ئەمڕۆ
                </span>
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center text-lg shadow-inner">
                    📤
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span id="kpi-total-exits" class="text-3xl sm:text-4xl font-black text-white tracking-tight font-mono">
                    {{ $totalExitsToday }}
                </span>
                <span class="text-xs text-slate-400 font-bold">تەواوبوو</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2 flex items-center justify-between">
                <span>هۆڵیان جێهێشتووە</span>
                <span class="text-amber-400 font-bold group-hover:translate-x-[-4px] transition-transform">←</span>
            </p>
        </a>

        <!-- Card 4: تێکڕای کاتی مانەوەی یاریزان -->
        <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 to-teal-950/40 border border-slate-800 rounded-3xl p-5 shadow-xl">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black text-teal-300/90">
                    ⏱️ تێکڕای کاتی مانەوە
                </span>
                <div class="w-10 h-10 rounded-2xl bg-teal-500/10 text-teal-400 border border-teal-500/20 flex items-center justify-center text-lg shadow-inner">
                    ⏳
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black text-teal-300 tracking-tight">
                    {{ $avgDurationFormatted }}
                </span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2 flex items-center justify-between">
                <span>تێکڕای ماوەی یاریزان لە هۆڵ</span>
                <span class="text-teal-400 font-bold">DAILY AVG</span>
            </p>
        </div>

    </div>

    <!-- 3. MAIN CENTERED PUNCH & HARDWARE SCANNER STATION (فۆرمی هاتنی ڕۆژانە لە ناوەڕاستەوە بە شێوەیەکی پڕۆفشناڵ) -->
    <div class="max-w-4xl mx-auto">
        <div id="punch-main-card" class="relative bg-slate-900/95 backdrop-blur-2xl border-2 border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl transition-all duration-300 overflow-hidden">
            
            <!-- Glow Accents inside the card -->
            <div id="card-glow-bg" class="absolute -top-32 left-1/2 -translate-x-1/2 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none transition-all duration-500"></div>

            <!-- Top Header & Mode Toggle -->
            <div class="relative flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-5 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div id="gate-icon-pulse" class="w-11 h-11 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-xl shadow-lg">
                        ⚡
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-white">تێکست بۆکسی لێدانی کارت و بارکۆد</h2>
                        <p class="text-xs text-slate-400">کارت یان بارکۆدی مۆبایل لەبەرامبەر سکەنەرەکە بگرە</p>
                    </div>
                </div>

                <!-- Punch Direction Switcher (خۆکار، هاتن، دەرچوون) -->
                <div class="flex items-center gap-1 bg-slate-950 p-1.5 rounded-2xl border border-slate-800 text-xs font-bold w-full sm:w-auto justify-center">
                    <button type="button" onclick="setPunchMode('auto')" id="mode-btn-auto" class="flex-1 sm:flex-none px-4 py-1.5 rounded-xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-1.5">
                        <span>🔄</span>
                        <span>خۆکار (Auto)</span>
                    </button>
                    <button type="button" onclick="setPunchMode('entry')" id="mode-btn-entry" class="flex-1 sm:flex-none px-3.5 py-1.5 rounded-xl text-slate-400 hover:text-white transition flex items-center justify-center gap-1.5">
                        <span>📥</span>
                        <span>هاتن</span>
                    </button>
                    <button type="button" onclick="setPunchMode('exit')" id="mode-btn-exit" class="flex-1 sm:flex-none px-3.5 py-1.5 rounded-xl text-slate-400 hover:text-white transition flex items-center justify-center gap-1.5">
                        <span>📤</span>
                        <span>دەرچوون</span>
                    </button>
                </div>
            </div>

            <!-- دوگمەی تایبەتی کردنەوەی دەرگا بۆ میوان یان ناونووسین بەبێ کۆد (Guest & Walk-in Direct Door Release) -->
            <div class="mt-5 p-4 rounded-2xl bg-gradient-to-r from-emerald-950/40 via-slate-950 to-teal-950/40 border border-emerald-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-lg relative overflow-hidden">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-2xl flex-shrink-0 shadow-inner">
                        🚪
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-white">کردنەوەی دەرگا بۆ میوان یان ناونووسین</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                بێ کۆد • Direct Release
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            ئەگەر کەسێک بە میوانی بێت یان بۆ خۆتۆمارکردنی نوێ هاتبێت، بە یەک کلیک دەرگاکەی بۆ بکەرەوە
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <!-- دوگمەی سەرەکی کردنەوەی ڕاستەوخۆ (One-Click Instant Open) -->
                    <button type="button" 
                            id="btn-guest-gate-open"
                            onclick="manualOpenGate()" 
                            class="flex-1 sm:flex-initial px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-xs sm:text-sm shadow-xl shadow-emerald-600/30 transition-all duration-200 hover:scale-[1.03] active:scale-[0.97] flex items-center justify-center gap-2">
                        <span class="text-base" id="guest-gate-btn-icon">🔓</span>
                        <span id="guest-gate-btn-text">کردنەوەی دەرگا (میوان)</span>
                    </button>

                    <!-- دوگمەی مۆداڵی ناونووسینی ناوی میوان (ئارەزوومەندانە) -->
                    <button type="button" 
                            onclick="openGuestModal()" 
                            class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs border border-slate-700 transition flex items-center gap-1.5"
                            title="تۆمارکردنی ناوی میوان لە لۆگ">
                        <span>📝</span>
                        <span class="hidden md:inline">ناوی میوان</span>
                    </button>
                </div>
            </div>

            <!-- The Big Prominent Input Box (تێکست بۆکسی لێدانی کارت بەجوانی لەسەرەوە) -->
            <form id="punch-form" onsubmit="submitPunch(event)" class="relative my-6">
                <label for="scanned_code_input" class="block text-xs font-bold text-slate-300 mb-2 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span>💳</span>
                        <span>کۆدی کارت، بارکۆد، یان کۆدی یاریزان (Member Code / Barcode):</span>
                    </span>
                    <span class="text-[11px] text-emerald-400 font-mono">Autofocus Ready • کلیکی ئینتەر بکە</span>
                </label>

                <div class="relative flex items-center">
                    <div class="absolute right-4 text-xl text-slate-400 pointer-events-none">
                        🔍
                    </div>

                    <input type="text" 
                           id="scanned_code_input" 
                           name="scanned_code" 
                           required 
                           placeholder="کۆدی بارکۆد لێرە لێبدە یان سکەنی بکە..." 
                           class="w-full bg-slate-950/90 border-2 border-slate-700 focus:border-emerald-500 rounded-2xl pr-12 pl-36 py-4 text-base sm:text-lg text-white font-mono placeholder-slate-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 transition-all duration-200 text-left" 
                           autofocus 
                           autocomplete="off">

                    <!-- Instant Submit Action Button on the left inside input -->
                    <div class="absolute left-2.5">
                        <button type="submit" id="btn-punch-submit" class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black px-5 py-2.5 rounded-xl text-xs sm:text-sm transition shadow-lg shadow-emerald-600/30 flex items-center gap-2 hover:scale-[1.02] active:scale-[0.98]">
                            <span>⚡ لێدان</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- LIVE VERIFICATION RESULT CARD (هاوشێوەی دەستکارییەکانی ناو ئەکسێس بە بەرزترین ئاست) -->
            <div id="member-live-result" class="relative bg-slate-950/70 border border-slate-800 rounded-2xl p-5 transition-all duration-300">
                
                <!-- Status Header Banner -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pb-3 border-b border-slate-800">
                    <div id="badge-access-status" class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span>
                        <span>چاوەڕوانی سکەنکردنە؛ تکایە کارت یان بارکۆد لێبدە...</span>
                    </div>

                    <div id="relay-pulse-status" class="text-xs font-mono font-bold text-slate-500 flex items-center gap-2">
                        <span>دۆخی قفڵی دەرگا:</span>
                        <span id="relay-state-label" class="px-2.5 py-0.5 rounded-md bg-slate-900 border border-slate-800 text-slate-400">
                            🔒 داخراوە (LOCKED)
                        </span>
                    </div>
                </div>

                <!-- Main Player Profile & Subscription Card -->
                <div class="py-4 flex flex-col md:flex-row items-center md:items-start gap-5 text-center md:text-right">
                    <!-- Player Photo -->
                    <div id="player-avatar-box" class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-3xl font-bold text-slate-300 shadow-xl overflow-hidden flex-shrink-0 transition-transform duration-300">
                        👤
                    </div>

                    <!-- Player Info Details -->
                    <div class="flex-1 space-y-2 w-full">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h3 id="display-player-name" class="text-xl font-black text-white">
                                    هێشتا هیچ کارتێک لێنەدراوە
                                </h3>
                                <p id="display-player-code" class="text-xs font-mono text-slate-400 mt-0.5">
                                    BARCODE: ----------
                                </p>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap mx-auto sm:mx-0">
                                <!-- Remaining Days Big Pill Badge (بڕی ماوەی بەشداری) -->
                                <div id="badge-remaining-pill" class="hidden px-4 py-1.5 rounded-2xl font-bold text-xs border w-fit">
                                    <span id="text-remaining-days">-- ڕۆژ ماوە</span>
                                </div>

                                <!-- Debt Warning Pill (ئاگاداری قەرز) -->
                                <div id="badge-debt-pill" class="hidden px-4 py-1.5 rounded-2xl font-black text-xs bg-rose-500/20 text-rose-300 border border-rose-500/40 font-mono animate-pulse flex items-center gap-1.5 shadow-md shadow-rose-900/30">
                                    <span>⚠️</span>
                                    <span id="text-debt-amount">قەرزارە: 0 IQD</span>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Info Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 text-xs">
                            <div class="bg-slate-900/90 p-2.5 rounded-xl border border-slate-800/80">
                                <span class="text-[10px] text-slate-500 block">جۆری بەشداری (Plan):</span>
                                <span id="display-player-plan" class="font-bold text-white block mt-0.5">---</span>
                            </div>
                            <div class="bg-slate-900/90 p-2.5 rounded-xl border border-slate-800/80">
                                <span class="text-[10px] text-slate-500 block">بەسەرچوون (Expiry):</span>
                                <span id="display-player-expiry" class="font-bold text-slate-300 block mt-0.5 font-mono">---</span>
                            </div>
                            <div class="bg-slate-900/90 p-2.5 rounded-xl border border-slate-800/80">
                                <span class="text-[10px] text-slate-500 block">کاتی تۆمارکردن:</span>
                                <span id="display-action-time" class="font-bold text-slate-300 block mt-0.5 font-mono">---</span>
                            </div>
                            <div class="bg-slate-900/90 p-2.5 rounded-xl border border-slate-800/80">
                                <span class="text-[10px] text-slate-500 block">ماوەی ڕاهێنان لە هۆڵ:</span>
                                <span id="display-player-duration" class="font-bold text-emerald-400 block mt-0.5">---</span>
                            </div>
                        </div>

                        <!-- Alert / Message Banner -->
                        <div id="scan-alert-box" class="hidden mt-3 p-3 rounded-2xl text-xs font-bold transition-all"></div>
                    </div>
                </div>

            </div>

            <!-- Arduino Hardware Integration & Test Actions Toolbar (لەژێر فۆرمەکە) -->
            <div class="mt-6 pt-4 border-t border-slate-800/90 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-pulse"></span>
                    <span class="font-bold text-slate-300">ئاردینۆ مێگا (Arduino Relay 2560):</span>
                    <span id="hardware-port-badge" class="px-2.5 py-0.5 rounded-full font-mono text-[11px] bg-slate-800 text-slate-400 border border-slate-700">
                        {{ config('arduino.port', 'COM3') }} @ {{ config('arduino.baud', 9600) }}bps
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Connect Web Serial (Direct Browser to Arduino USB) -->
                    <button type="button" onclick="connectArduinoSerial()" id="btn-connect-arduino" class="px-3.5 py-1.5 rounded-xl bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-300 border border-cyan-500/30 font-bold transition flex items-center gap-1.5">
                        <span>🔌</span>
                        <span id="connect-btn-text">پەیوەستکردنی USB (Web Serial)</span>
                    </button>

                    <!-- Manual Relay Click Test -->
                    <button type="button" onclick="testArduinoRelay()" id="btn-test-relay" class="px-3.5 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 font-bold transition flex items-center gap-1.5">
                        <span>⚡</span>
                        <span>تاقیکردنەوەی چرکاندنی ڕیلەی</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- 4. QUICK TEST STRIP (تاقیکردنەوەی خێرا بە یەک کلیک بۆ یاریزانانی چالاک، بەسەرچوو، و هۆڵ) -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-xs font-black text-white flex items-center gap-2">
                <span>⚡ تاقیکردنەوەی خێرا بە یەک کلیک (One-Click Quick Test):</span>
                <span class="text-[10px] text-slate-400 font-normal">کلیک لەسەر هەر یاریزانێک بکە بۆ لێدانی ڕاستەوخۆ</span>
            </h3>
            <span class="text-[11px] text-slate-500 font-mono">Anti-Passback: 5 Mins</span>
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
            @foreach($members->take(8) as $qm)
                @php
                    $isInside = $qm->currentAttendance !== null;
                    $statusColor = match($qm->status) {
                        'active' => 'emerald',
                        'expired' => 'rose',
                        'frozen' => 'amber',
                        default => 'slate'
                    };
                    $token = $qm->primaryCredential?->raw_token ?: $qm->member_code;
                @endphp
                <button type="button" onclick="quickPunchPlayer('{{ $token }}')" class="flex-shrink-0 px-3.5 py-2 rounded-2xl border border-{{ $statusColor }}-500/30 bg-{{ $statusColor }}-500/10 hover:bg-{{ $statusColor }}-500/20 transition flex items-center gap-2.5 text-right group">
                    <div class="w-7 h-7 rounded-lg bg-slate-800 flex items-center justify-center text-xs font-bold text-white">
                        {{ mb_substr($qm->full_name, 0, 1) }}
                    </div>
                    <div>
                        <span class="text-xs font-bold text-white block group-hover:text-{{ $statusColor }}-300 transition">
                            {{ $qm->full_name }}
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono block">
                            {{ $qm->member_code }} • {{ $isInside ? 'لە ژوورەوەیە' : $qm->status }}
                        </span>
                    </div>
                    <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase {{ $isInside ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-300' }}">
                        {{ $isInside ? '📤 دەرچوون' : '📥 هاتن' }}
                    </span>
                </button>
            @endforeach

            <!-- Test Fake Card Button (هاوشێوەی ئەکسێس: تۆمار نەکراوە) -->
            <button type="button" onclick="quickPunchPlayer('INVALID_UNKNOWN_CODE_9999')" class="flex-shrink-0 px-3.5 py-2 rounded-2xl border border-rose-500/30 bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 text-xs font-bold transition flex items-center gap-2">
                <span>❌</span>
                <span>کۆدی تۆمارنەکراو</span>
            </button>
        </div>
    </div>

    <!-- 5. ATTENDEES TABLE (خشتەی یاریزانە هاتووەکان بە کاتی هاتن، چوون، بارکۆد، بڕی ماوە و جۆری بەشداری) -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        
        <!-- Table Header & Search Filter Bar -->
        <div class="p-5 border-b border-slate-800 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <h3 class="text-lg font-black text-white flex items-center gap-2">
                    <span>📋 خشتەی یاریزانە هاتووەکان و سەردانەکانی ئەمڕۆ</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    پیشاندانی کاتی هاتن و چوون، بارکۆد، بڕی ماوەی ڕۆژەکانی بەشداری، و جۆری پلان
                </p>
            </div>

            <!-- Tab Buttons & Live Search -->
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                <!-- Live Client-side Filter Search Box -->
                <div class="relative w-full sm:w-64">
                    <input type="text" 
                           id="table-search-input" 
                           oninput="filterAttendeesTable()" 
                           placeholder="گەڕان بەپێی ناو یان بارکۆد..." 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl pr-9 pl-3 py-2 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition font-sans">
                    <span class="absolute right-3 top-2.5 text-xs text-slate-400">🔍</span>
                </div>

                <!-- Tabs (هەموو، لە هۆڵدان، دەرچوون) -->
                <div class="flex items-center gap-1 bg-slate-950 p-1 rounded-xl border border-slate-800 text-xs font-bold w-full sm:w-auto justify-center">
                    <a href="{{ route('attendance.index', ['tab' => 'all']) }}" 
                       class="px-3 py-1.5 rounded-lg transition {{ $tab === 'all' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
                        هەموو ({{ $totalEntriesToday }})
                    </a>
                    <a href="{{ route('attendance.index', ['tab' => 'inside']) }}" 
                       class="px-3 py-1.5 rounded-lg transition {{ $tab === 'inside' ? 'bg-emerald-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
                        لە هۆڵدان ({{ $currentlyInsideCount }})
                    </a>
                    <a href="{{ route('attendance.index', ['tab' => 'exits']) }}" 
                       class="px-3 py-1.5 rounded-lg transition {{ $tab === 'exits' ? 'bg-amber-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
                        دەرچوون ({{ $totalExitsToday }})
                    </a>
                </div>
            </div>
        </div>

        <!-- The Attendees Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-950/80 text-slate-400 uppercase text-[11px] font-bold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">#</th>
                        <th class="py-3.5 px-4">یاریزان (Player)</th>
                        <th class="py-3.5 px-4 font-mono">بارکۆد / کۆد</th>
                        <th class="py-3.5 px-4">جۆری بەشداری (Plan)</th>
                        <th class="py-3.5 px-4">کاتی هاتن</th>
                        <th class="py-3.5 px-4">کاتی دەرچوون</th>
                        <th class="py-3.5 px-4">ماوەی مانەوە</th>
                        <th class="py-3.5 px-4">بڕی ماوەی بەشداری</th>
                        <th class="py-3.5 px-4 text-center">کردارەکان</th>
                    </tr>
                </thead>
                <tbody id="attendees-table-body" class="divide-y divide-slate-800/80">
                    @forelse($attendances as $index => $att)
                        @php
                            $m = $att->member;
                            $membership = $att->membership ?? $m?->activeMembership ?? $m?->memberships?->first();
                            $plan = $membership?->plan;
                            $isInside = ($att->status === 'INSIDE');
                            
                            // بڕی ماوەی ڕۆژەکانی بەشداری
                            $daysLeft = null;
                            $isExpired = false;
                            if ($membership && $membership->end_date) {
                                $daysLeft = (int) now()->diffInDays($membership->end_date, false);
                                $isExpired = $daysLeft < 0;
                            }
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition attendee-row" 
                            data-name="{{ strtolower($m?->full_name ?? '') }}" 
                            data-code="{{ strtolower($m?->member_code ?? '') }}">
                            
                            <!-- Index -->
                            <td class="py-3.5 px-4 font-mono text-slate-500">
                                {{ $attendances->firstItem() + $index }}
                            </td>

                            <!-- Player Info (Photo & Name) -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-white text-xs overflow-hidden flex-shrink-0">
                                        @if($m?->photo_url)
                                            <img src="{{ $m->photo_url }}" alt="{{ $m->full_name }}" class="w-full h-full object-cover">
                                        @else
                                            {{ mb_substr($m?->full_name ?? 'ـ', 0, 1) }}
                                        @endif
                                    </div>
                                    <div>
                                        <span class="font-bold text-white text-sm block">
                                            {{ $m?->full_name ?? 'سڕاوەتەوە' }}
                                        </span>
                                        <span class="text-[11px] text-slate-400 font-mono block">
                                            {{ $m?->phone ?? 'بێ ژمارە' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Barcode / Member Code -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-mono text-xs font-bold bg-slate-950 text-slate-300 border border-slate-800">
                                    <span>🏷️</span>
                                    <span>{{ $m?->member_code ?? '---' }}</span>
                                </span>
                            </td>

                            <!-- Plan Type -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                    {{ $plan?->name ?? 'پلانی ئاسایی' }}
                                </span>
                            </td>

                            <!-- Check-in Time -->
                            <td class="py-3.5 px-4 font-mono text-slate-200">
                                <span class="inline-flex items-center gap-1 text-slate-300">
                                    <span class="text-emerald-400">📥</span>
                                    <span>{{ $att->check_in_at ? $att->check_in_at->format('h:i A') : 'ـ' }}</span>
                                </span>
                            </td>

                            <!-- Check-out Time -->
                            <td class="py-3.5 px-4 font-mono">
                                @if($att->check_out_at)
                                    <span class="inline-flex items-center gap-1 text-slate-400">
                                        <span class="text-amber-400">📤</span>
                                        <span>{{ $att->check_out_at->format('h:i A') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                        لە ناوەوەیە
                                    </span>
                                @endif
                            </td>

                            <!-- Duration -->
                            <td class="py-3.5 px-4">
                                <span class="font-bold {{ $isInside ? 'text-emerald-400' : 'text-slate-300' }}">
                                    {{ $att->duration_formatted }}
                                </span>
                            </td>

                            <!-- Remaining Subscription / Days left (بڕی ماوەی بەشداری) -->
                            <td class="py-3.5 px-4">
                                @if($membership && $membership->end_date)
                                    @if($isExpired)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-black bg-rose-500/10 text-rose-400 border border-rose-500/30 font-mono">
                                            <span>🔴</span>
                                            <span>بەسەرچووە ({{ abs($daysLeft) }} ڕۆژ پێش ئێستا)</span>
                                        </span>
                                    @elseif($daysLeft <= 5)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-black bg-amber-500/10 text-amber-300 border border-amber-500/30 font-mono animate-pulse">
                                            <span>⚠️</span>
                                            <span>{{ $daysLeft }} ڕۆژ ماوە (نزیکە)</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-black bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 font-mono">
                                            <span>🟢</span>
                                            <span>{{ $daysLeft }} ڕۆژ ماوە</span>
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-500">دیارینەکراو</span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="py-3.5 px-4 text-center">
                                @if($isInside)
                                    <form action="{{ route('attendance.checkout', $att) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 rounded-xl text-xs font-bold bg-amber-600/20 hover:bg-amber-600 text-amber-300 hover:text-white border border-amber-500/30 transition shadow">
                                            دەرچوون 📤
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-500 text-[11px]">تەواوبووە ✓</span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">📋</div>
                                <div class="text-sm font-bold text-slate-400">هیچ یاریزانێک بۆ ئەم فلتەرە نەدۆزرایەوە.</div>
                                <div class="text-xs text-slate-500 mt-1">کاتێک یاریزان کارت لێدەدات، لێرە تۆمار دەبێت.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($attendances->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950/60 flex justify-between items-center text-xs">
                {{ $attendances->links() }}
            </div>
        @endif

    </div>

</div>

<!-- ================= Guest Registration / Gate Release Modal (مۆداڵی تۆماری میوان و کردنەوەی دەرگا) ================= -->
<div id="guest-open-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm hidden animate-in fade-in duration-200">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl text-white space-y-4 relative">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 text-lg border border-emerald-500/20">🚪</span>
                <div>
                    <h3 class="font-black text-sm text-white">کردنەوەی دەرگا بۆ میوان یان ناونووسین</h3>
                    <p class="text-[11px] text-slate-400">تۆمارکردنی زانیاری سەردان لە لۆگی سیستەم</p>
                </div>
            </div>
            <button type="button" onclick="closeGuestModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center font-bold text-sm transition">
                ✕
            </button>
        </div>

        <p class="text-xs text-slate-300 leading-relaxed">
            دەتوانیت ناوی میوان یان سەردانیکەر بنووسیت تا لە ڕاپۆرتی لۆگی ئەکسێس و سەردانەکانی ئەمڕۆدا بە ڕوونی تۆمار بکرێت:
        </p>

        <div class="space-y-3.5">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">ناوی میوان یان سەردانیکەر (ئارەزوومەندانە):</label>
                <input type="text" id="modal-guest-name" placeholder="بۆ نموونە: کاک محمد (میوانی هۆڵ)" class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">هۆکاری سەردان:</label>
                <select id="modal-guest-reason" class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none transition">
                    <option value="بۆ ناونووسین و بەشداری نوێ هاتووە">📝 بۆ ناونووسین و بەشداری نوێ هاتووە</option>
                    <option value="میوانە و بۆ تاقیکردنەوە هاتووە (Free Trial)">🏋️‍♂️ میوانە و بۆ تاقیکردنەوە هاتووە (Trial)</option>
                    <option value="سەردانیکەر و ڕاوێژکاری وەرزشی">🤝 سەردانیکەر و ڕاوێژکاری وەرزشی</option>
                    <option value="کردنەوەی دەستی لەلایەن وەرگری هۆڵ">🔓 کردنەوەی دەستی لەلایەن کارمەندی پێشوازی</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800">
            <button type="button" onclick="closeGuestModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition">
                پەشیمانبوونەوە
            </button>
            <button type="button" id="btn-modal-guest-submit" onclick="submitModalGuestOpen()" class="px-5 py-2.5 rounded-xl text-xs font-black text-white bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-400 shadow-lg shadow-emerald-600/30 transition-all hover:scale-105 active:scale-95 flex items-center gap-1.5">
                <span>🔓</span>
                <span>کردنەوەی دەرگا و تۆمارکردن</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Audio settings state
    let isSoundEnabled = true;
    let currentPunchMode = 'auto'; // auto, entry, exit

    // Live Digital Clock
    function updateLiveClock() {
        const el = document.getElementById('live-digital-clock');
        if (el) {
            const now = new Date();
            el.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        }
    }
    setInterval(updateLiveClock, 1000);

    function toggleAudio() {
        isSoundEnabled = !isSoundEnabled;
        const icon = document.getElementById('sound-icon');
        icon.textContent = isSoundEnabled ? '🔊' : '🔇';
    }

    // Punch Mode Switcher
    function setPunchMode(mode) {
        currentPunchMode = mode;
        const btnAuto = document.getElementById('mode-btn-auto');
        const btnEntry = document.getElementById('mode-btn-entry');
        const btnExit = document.getElementById('mode-btn-exit');

        btnAuto.className = 'flex-1 sm:flex-none px-4 py-1.5 rounded-xl text-slate-400 hover:text-white transition flex items-center justify-center gap-1.5';
        btnEntry.className = 'flex-1 sm:flex-none px-3.5 py-1.5 rounded-xl text-slate-400 hover:text-white transition flex items-center justify-center gap-1.5';
        btnExit.className = 'flex-1 sm:flex-none px-3.5 py-1.5 rounded-xl text-slate-400 hover:text-white transition flex items-center justify-center gap-1.5';

        if (mode === 'auto') {
            btnAuto.className = 'flex-1 sm:flex-none px-4 py-1.5 rounded-xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-1.5';
        } else if (mode === 'entry') {
            btnEntry.className = 'flex-1 sm:flex-none px-3.5 py-1.5 rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-1.5';
        } else if (mode === 'exit') {
            btnExit.className = 'flex-1 sm:flex-none px-3.5 py-1.5 rounded-xl bg-amber-600 text-white shadow-lg shadow-amber-600/30 transition flex items-center justify-center gap-1.5';
        }
    }

    // Fast Punch from Quick Strip
    async function quickPunchPlayer(code) {
        const input = document.getElementById('scanned_code_input');
        input.value = code;
        await processPunchRequest(code, currentPunchMode);
    }

    // Form submit
    async function submitPunch(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('scanned_code_input');
        const code = input.value.trim();
        if (!code) return;

        await processPunchRequest(code, currentPunchMode);
        input.value = '';
        input.focus();
    }

    // MAIN PUNCH LOGIC - EXACTLY MATCHES ACCESS & HARDWARE
    async function processPunchRequest(code, mode) {
        const mainCard = document.getElementById('punch-main-card');
        const alertBox = document.getElementById('scan-alert-box');
        const statusBadge = document.getElementById('badge-access-status');
        const relayLabel = document.getElementById('relay-state-label');
        const playerNameDisplay = document.getElementById('display-player-name');
        const playerCodeDisplay = document.getElementById('display-player-code');
        const playerPlanDisplay = document.getElementById('display-player-plan');
        const playerExpiryDisplay = document.getElementById('display-player-expiry');
        const actionTimeDisplay = document.getElementById('display-action-time');
        const durationDisplay = document.getElementById('display-player-duration');
        const remainingPill = document.getElementById('badge-remaining-pill');
        const remainingText = document.getElementById('text-remaining-days');
        const avatarBox = document.getElementById('player-avatar-box');

        // Visual loading state
        statusBadge.innerHTML = '<span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-spin"></span><span>پشکنینی مەرجەکانی بەشداری و لێدانی کارت...</span>';
        statusBadge.className = 'inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30';
        mainCard.className = 'relative bg-slate-900/95 backdrop-blur-2xl border-2 border-amber-500/50 rounded-3xl p-6 sm:p-8 shadow-2xl transition-all duration-300';

        try {
            const response = await fetch('{{ route("attendance.punch") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    scanned_code: code,
                    mode: mode,
                    has_web_serial: !!webSerialWriter
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // ١. ڕێگەپێدراوە (GRANTED) - هاوشێوەی ئەکسێس If Expire > 0
                const mem = data.member;

                playerNameDisplay.textContent = mem.name;
                playerCodeDisplay.textContent = 'BARCODE: ' + mem.code;
                playerPlanDisplay.textContent = mem.plan_name;
                playerExpiryDisplay.textContent = mem.expires_at;
                actionTimeDisplay.textContent = mem.action_time;
                durationDisplay.textContent = mem.duration_formatted || (mem.is_inside ? 'تازە هاتە ژوورەوە' : 'تەواوبوو');

                // Days left pill
                remainingPill.classList.remove('hidden');
                if (mem.days_left > 5) {
                    remainingPill.className = 'px-4 py-1.5 rounded-2xl font-black text-xs bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-mono';
                    remainingText.textContent = '🟢 ' + mem.days_left + ' ڕۆژ ماوە';
                } else if (mem.days_left > 0) {
                    remainingPill.className = 'px-4 py-1.5 rounded-2xl font-black text-xs bg-amber-500/20 text-amber-300 border border-amber-500/40 font-mono';
                    remainingText.textContent = '⚠️ ' + mem.days_left + ' ڕۆژ ماوە (نزیکە)';
                } else {
                    remainingPill.className = 'px-4 py-1.5 rounded-2xl font-black text-xs bg-rose-500/20 text-rose-300 border border-rose-500/40 font-mono';
                    remainingText.textContent = '🔴 بەسەرچووە';
                }

                // Avatar
                if (mem.photo_url) {
                    avatarBox.innerHTML = `<img src="${mem.photo_url}" class="w-full h-full object-cover">`;
                } else {
                    avatarBox.innerHTML = mem.name.substring(0, 1);
                }

                // Debt Warning Pill & Audio Alert
                const debtPill = document.getElementById('badge-debt-pill');
                if (mem.has_debt) {
                    debtPill.classList.remove('hidden');
                    document.getElementById('text-debt-amount').textContent = '⚠️ قەرزارە: ' + Number(mem.current_debt).toLocaleString() + ' IQD';
                    playTone(330);
                    setTimeout(() => playTone(280), 180);
                    speakVoice("ئاگاداری: یاریزان قەرزی لەسەرە");
                } else {
                    debtPill.classList.add('hidden');
                }

                if (data.action === 'ENTRY') {
                    // شێوازی شین و سەوز بۆ بەخێرهاتن هاوشێوەی vbBlue
                    statusBadge.innerHTML = '<span class="w-2.5 h-2.5 rounded-full bg-blue-400 animate-pulse"></span><span>بەخێربێیت بەڕێز ' + mem.name + '</span>';
                    statusBadge.className = 'inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-black bg-blue-500/20 text-blue-300 border border-blue-500/40';
                    mainCard.className = 'relative bg-slate-900/95 backdrop-blur-2xl border-2 border-blue-500 rounded-3xl p-6 sm:p-8 shadow-2xl ring-4 ring-blue-500/20 transition-all duration-300';

                    // قفڵی دەرگا کرایەوە
                    relayLabel.innerHTML = '🔓 کرایەوە بۆ ٣ چرکە (OPEN)';
                    relayLabel.className = 'px-2.5 py-0.5 rounded-md bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 font-bold animate-pulse';

                    // ناردنی سیگناڵ بۆ ئاردینۆ
                    sendArduinoPulse();

                    // خوێندنەوەی دەنگی کوردی (Speech)
                    speakVoice("بەخێربێیت بەڕێز " + mem.name);

                    // دەنگی بیپی سەرکەوتن
                    playTone(880);

                    // گەڕانەوەی دۆخی قفڵ پاش ٣ چرکە
                    setTimeout(() => {
                        relayLabel.innerHTML = '🔒 داخراوە (LOCKED)';
                        relayLabel.className = 'px-2.5 py-0.5 rounded-md bg-slate-900 border border-slate-800 text-slate-400';
                    }, 3000);

                } else {
                    // دەرچوون (Check-out)
                    statusBadge.innerHTML = '<span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span><span>چوونەدەرەوە تۆمارکرا</span>';
                    statusBadge.className = 'inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-black bg-amber-500/20 text-amber-300 border border-amber-500/40';
                    mainCard.className = 'relative bg-slate-900/95 backdrop-blur-2xl border-2 border-amber-500 rounded-3xl p-6 sm:p-8 shadow-2xl ring-4 ring-amber-500/20 transition-all duration-300';

                    speakVoice("دەرچوون تۆمارکرا، بەهیوای کاتێکی خۆش " + mem.name);
                    playTone(550);
                }

                alertBox.className = 'mt-3 p-3.5 rounded-2xl text-xs font-bold bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 block';
                alertBox.textContent = '✓ ' + data.message;

                // KPI Counter نوێکردنەوە
                if (data.stats) {
                    document.getElementById('kpi-total-entries').textContent = data.stats.total_entries;
                    document.getElementById('kpi-total-exits').textContent = data.stats.total_exits;
                    document.getElementById('kpi-currently-inside').textContent = data.stats.currently_inside;
                }

                // ئامادەکردنی خانەی نووسین بۆ یاریزانی دواتر بێ ئەوەی پەڕەکە ڕیفریش ببێتەوە و پەیوەندی ئاردینۆ بپچڕێت
                setTimeout(() => {
                    input.value = '';
                    input.disabled = false;
                    input.focus();
                }, 1800);

            } else {
                // ٢. ڕەتکردنەوە (DENIED) - هاوشێوەی مەرجەکانی ئەکسێس
                let deniedText = "تۆمار نەکراوە";
                let voiceText = "تۆمار نەکراوە";

                if (data.reason === 'membership_expired' || (data.message && data.message.includes('بەسەرچووە'))) {
                    deniedText = "ماوەی بەشداربوونەت تەواو بووە";
                    voiceText = "ماوەی بەشداربوونەت تەواو بووە";
                } else if (data.reason === 'member_not_found') {
                    deniedText = "تۆمار نەکراوە";
                    voiceText = "تۆمار نەکراوە";
                } else if (data.message) {
                    deniedText = data.message;
                    voiceText = data.message;
                }

                statusBadge.innerHTML = '<span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-bounce"></span><span>' + deniedText + '</span>';
                statusBadge.className = 'inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-black bg-rose-500/20 text-rose-300 border border-rose-500/40';
                mainCard.className = 'relative bg-slate-900/95 backdrop-blur-2xl border-2 border-rose-500 rounded-3xl p-6 sm:p-8 shadow-2xl ring-4 ring-rose-500/20 transition-all duration-300';

                alertBox.className = 'mt-3 p-3.5 rounded-2xl text-xs font-bold bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
                alertBox.textContent = '⛔ ' + deniedText;

                relayLabel.innerHTML = '🔒 ڕەتکرایەوە (DENIED)';
                relayLabel.className = 'px-2.5 py-0.5 rounded-md bg-rose-500/20 border border-rose-500/40 text-rose-400 font-bold';

                // خوێندنەوەی دەنگی هۆشداری
                speakVoice(voiceText);

                // دەنگی هەڵە
                playTone(220);
            }

        } catch (err) {
            console.error(err);
            statusBadge.innerHTML = 'هەڵەی پەیوەندی بە سێرڤەر';
            statusBadge.className = 'inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-300';
            alertBox.className = 'mt-3 p-3.5 rounded-2xl text-xs font-bold bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
            alertBox.textContent = 'پەیوەندی لەگەڵ سێرڤەر نەبەسترا.';
        }
    }

    // ==========================================
    // 🔌 پەیوەندی بە ئاردینۆ و چرکاندنی ڕیلەی
    // ==========================================
    let webSerialPort = null;
    let webSerialWriter = null;

    async function connectArduinoSerial() {
        if (!('serial' in navigator)) {
            alert('براوسەرەکەت پشتگیری پەیوەندی ڕاستەوخۆی USB (Web Serial) ناکات.\nتکایە Google Chrome یان Microsoft Edge بەکاربهێنە.');
            return;
        }

        try {
            webSerialPort = await navigator.serial.requestPort();
            await webSerialPort.open({ baudRate: 9600 });
            webSerialWriter = webSerialPort.writable.getWriter();

            const badge = document.getElementById('hardware-port-badge');
            const btnText = document.getElementById('connect-btn-text');
            const dot = document.getElementById('arduino-dot');
            const indLabel = document.getElementById('arduino-status-label');

            badge.innerText = 'پەیوەستە بە USB (Web Serial 9600)';
            badge.className = 'px-2.5 py-0.5 rounded-full font-mono text-[11px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
            btnText.innerText = 'ئاردینۆ پەیوەستە ✓';
            dot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping';
            indLabel.innerText = 'ONLINE (USB)';
            indLabel.className = 'font-bold text-emerald-400 text-xs font-mono';

            speakVoice('ئاردینۆ بە سەرکەوتوویی پەیوەست کرا');
        } catch (err) {
            console.error('Serial connection error:', err);
            alert('هەڵە لە پەیوەندی ئاردینۆ: ' + err.message);
        }
    }

    async function sendArduinoPulse() {
        if (webSerialWriter) {
            try {
                // ناردنی ڕاستەوخۆی بایتی 49 (پیتی '1') بە تەواوی هاوشێوەی سیستەمی ئەکسسەکە بۆ ئاردینۆ نانۆ
                const pulse = new Uint8Array([49]); // ASCII 49 = '1'
                await webSerialWriter.write(pulse);
                console.log("✓ Gate pulse byte '1' (49) sent to Arduino Nano via Web Serial");
            } catch (err) {
                console.error("Web Serial write failed:", err);
            }
        }
    }

    async function testArduinoRelay() {
        const btn = document.getElementById('btn-test-relay');
        const orig = btn.innerHTML;
        btn.innerHTML = '<span>⏳</span><span>چرکاندن...</span>';
        btn.disabled = true;

        await sendArduinoPulse();

        try {
            const resp = await fetch('{{ route("attendance.arduino.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ command: '1' })
            });
            const res = await resp.json();
            alert(res.message);
        } catch (e) {
            console.error(e);
        } finally {
            btn.innerHTML = orig;
            btn.disabled = false;
        }
    }

    // دەنگی ئاگادارکردنەوە (Speech Synthesis)
    function speakVoice(text) {
        if (!isSoundEnabled) return;
        if ('speechSynthesis' in window) {
            try {
                window.speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance(text);
                utter.rate = 0.95;
                utter.pitch = 1.0;
                utter.lang = 'ar-SA';
                window.speechSynthesis.speak(utter);
            } catch (e) {}
        }
    }

    // Audio synthesizer beeps
    function playTone(freq) {
        if (!isSoundEnabled) return;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.frequency.value = freq;
            osc.connect(gain);
            gain.connect(ctx.destination);
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.18);
        } catch (e) {}
    }

    // Client-side search in Attendees table
    function filterAttendeesTable() {
        const term = document.getElementById('table-search-input').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.attendee-row');

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const code = row.getAttribute('data-code') || '';
            if (name.includes(term) || code.includes(term)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // ================= MANUAL GATE RELEASE FOR GUESTS / REGISTRATION (کردنەوەی دەستیی دەرگا بێ کۆد) =================
    function openGuestModal() {
        document.getElementById('modal-guest-name').value = '';
        document.getElementById('guest-open-modal').classList.remove('hidden');
        setTimeout(() => document.getElementById('modal-guest-name').focus(), 100);
    }

    function closeGuestModal() {
        document.getElementById('guest-open-modal').classList.add('hidden');
    }

    async function submitModalGuestOpen() {
        const name = document.getElementById('modal-guest-name').value.trim();
        const reason = document.getElementById('modal-guest-reason').value;
        const submitBtn = document.getElementById('btn-modal-guest-submit');
        const origText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>⏳</span><span>کردنەوەی دەرگا...</span>';

        await manualOpenGate(name, reason);

        submitBtn.disabled = false;
        submitBtn.innerHTML = origText;
    }

    async function manualOpenGate(guestName = null, reason = 'میوان / ناونووسینی نوێ') {
        const mainCard = document.getElementById('punch-main-card');
        const alertBox = document.getElementById('scan-alert-box');
        const statusBadge = document.getElementById('badge-access-status');
        const relayLabel = document.getElementById('relay-state-label');
        const playerNameDisplay = document.getElementById('display-player-name');
        const playerCodeDisplay = document.getElementById('display-player-code');
        const playerPlanDisplay = document.getElementById('display-player-plan');
        const playerExpiryDisplay = document.getElementById('display-player-expiry');
        const actionTimeDisplay = document.getElementById('display-action-time');
        const durationDisplay = document.getElementById('display-player-duration');
        const remainingPill = document.getElementById('badge-remaining-pill');
        const remainingText = document.getElementById('text-remaining-days');
        const avatarBox = document.getElementById('player-avatar-box');

        const btnOpen = document.getElementById('btn-guest-gate-open');
        const origBtnText = btnOpen ? btnOpen.innerHTML : '';
        if (btnOpen) {
            btnOpen.disabled = true;
            btnOpen.innerHTML = '<span class="animate-spin">⏳</span><span>دەکرێتەوە...</span>';
        }

        try {
            const response = await fetch('{{ route("attendance.gate.open") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    guest_name: guestName,
                    reason: reason
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Update live player profile display
                const displayName = guestName ? `میوان: ${guestName}` : 'میوانی بەڕێز / تۆمارکردنی نوێ';
                playerNameDisplay.textContent = displayName;
                playerCodeDisplay.textContent = 'کۆنترۆڵی دەستی (MANUAL GUEST)';
                playerPlanDisplay.textContent = reason || 'سەردانی میوان / ناونووسینی هۆڵ';
                playerExpiryDisplay.textContent = 'ڕێگەپێدراو (کردنەوەی دەستی لە پێشوازی)';
                actionTimeDisplay.textContent = data.opened_at;
                durationDisplay.textContent = 'تازە هاتە ژوورەوە (میوان)';

                remainingPill.classList.remove('hidden');
                remainingPill.className = 'px-4 py-1.5 rounded-2xl font-black text-xs bg-teal-500/20 text-teal-300 border border-teal-500/40 font-mono';
                remainingText.textContent = '🚪 سەردانی کراوە (میوان)';

                avatarBox.innerHTML = '🌟';
                avatarBox.className = 'w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-teal-600/30 border-2 border-teal-400 flex items-center justify-center text-3xl font-bold text-teal-200 shadow-xl overflow-hidden flex-shrink-0 transition-transform duration-300 scale-105';

                statusBadge.innerHTML = '<span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span><span class="font-black text-emerald-300">دەرگا بە سەرکەوتوویی کرایەوە بۆ میوان / ناونووسین 🚪</span>';
                statusBadge.className = 'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 shadow-lg';
                mainCard.className = 'relative bg-slate-900/95 backdrop-blur-2xl border-2 border-emerald-500 rounded-3xl p-6 sm:p-8 shadow-2xl ring-4 ring-emerald-500/30 transition-all duration-300';

                // Unlock Relay Indicator with countdown
                relayLabel.innerHTML = '🔓 دەرگا کرایەوە (٥ چرکە)';
                relayLabel.className = 'px-3 py-1 rounded-md bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 font-bold animate-pulse text-xs';

                // Sound & Speech
                speakVoice("بەخێربێن بۆ هۆڵەکەمان");
                playTone(880);

                // Alert notification
                alertBox.className = 'mt-3 p-3.5 rounded-2xl text-xs font-bold bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 block';
                alertBox.textContent = '✓ ' + data.message;

                // Close Guest modal if open
                closeGuestModal();

                // Revert door lock after 5 seconds
                let count = 5;
                const interval = setInterval(() => {
                    count--;
                    if (count > 0) {
                        relayLabel.innerHTML = `🔓 دەرگا کرایەوە (${count} چرکە)`;
                    } else {
                        clearInterval(interval);
                        relayLabel.innerHTML = '🔒 داخراوە (LOCKED)';
                        relayLabel.className = 'px-2.5 py-0.5 rounded-md bg-slate-900 border border-slate-800 text-slate-400 text-xs';
                    }
                }, 1000);

            } else {
                alert('هەڵە لە کردنەوەی دەرگا: ' + (data.message || 'کێشە لە پەیوەندی بە ئامێری دەرگا'));
            }
        } catch (err) {
            console.error(err);
            alert('کێشەیەک ڕوویدا لە پەیوەندی بە سێرڤەر بۆ کردنەوەی دەرگا.');
        } finally {
            if (btnOpen) {
                btnOpen.disabled = false;
                btnOpen.innerHTML = origBtnText;
            }
        }
    }
</script>
@endpush
@endsection
