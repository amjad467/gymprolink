<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Scopes\GymScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * پیشاندانی فۆرمی چوونەژوورەوە (Login Page)
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * ئەنجامدانی لۆگین بە ئیمەیڵ یان ژمارەی مۆبایل لەگەڵ پاراستنی Rate Limiting
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'تکایە ئیمەیڵ یان ژمارەی مۆبایل بنووسە.',
            'password.required' => 'تکایە وشەی نهێنی بنووسە.',
        ]);

        $loginInput = trim($request->input('login'));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // کلیلی سنووردارکردنی هەوڵە هەڵەکان (Rate Limiting Throttle Key)
        $throttleKey = Str::transliterate(Str::lower($loginInput) . '|' . $request->ip());

        // پشکنینی ئایا هەژمارەکە قوفڵکراوە بەهۆی ٥ هەوڵی هەڵە
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            AuditLog::create([
                'gym_id' => null,
                'user_id' => null,
                'action' => 'auth.account_locked',
                'auditable_type' => User::class,
                'auditable_id' => null,
                'new_values' => [
                    'attempted_login' => $loginInput,
                    'lockout_duration_seconds' => $seconds,
                    'ip' => $request->ip(),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->withInput($request->only('login', 'remember'))->withErrors([
                'login' => "⚠️ هەژمارەکەت بۆ ماوەی {$seconds} چرکە قوفڵ کرا بەهۆی هەوڵی زۆری هەڵە. تکایە چاوەڕوان بە پاشان تاقی بکەرەوە.",
            ]);
        }

        // پشکنینی ئایا ئیمەیڵە یان مۆبایل
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // دۆزینەوەی بەکارهێنەر
        $user = User::where($field, $loginInput)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 300); // قوفڵ بۆ ماوەی ٥ خولەک دوای ٥ هەوڵ

            AuditLog::create([
                'gym_id' => $user?->current_gym_id,
                'user_id' => $user?->id,
                'action' => 'auth.login_failed',
                'auditable_type' => User::class,
                'auditable_id' => $user?->id,
                'new_values' => [
                    'attempted_login' => $loginInput,
                    'attempts' => RateLimiter::attempts($throttleKey),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $remaining = RateLimiter::remaining($throttleKey, 5);

            return back()->withInput($request->only('login', 'remember'))->withErrors([
                'login' => "زانیارییەکان هەڵەیە! دڵنیابەرەوە لە ئیمەیڵ و وشەی نهێنی. (تەنها {$remaining} هەوڵت ماوە پێش قوفڵبوون).",
            ]);
        }

        if (!$user->is_active) {
            return back()->withInput($request->only('login'))->withErrors([
                'login' => 'هەژمارەکەت ناچالاک کراوە. تکایە پەیوەندی بە بەڕێوەبەری سەرەکی هۆڵەوە بکە.',
            ]);
        }

        // پشکنینی 2FA ئەگەر چالاک کرابێت بۆ ئەم بەکارهێنەرە
        if ($user->two_factor_enabled && !empty($user->security_pin)) {
            session([
                'auth.2fa.user_id' => $user->id,
                'auth.2fa.remember' => $remember,
            ]);

            return redirect()->route('login.2fa');
        }

        // پاککردنەوەی هەوڵە هەڵەکان
        RateLimiter::clear($throttleKey);

        // لۆگین کردنی بەکارهێنەر
        Auth::login($user, $remember);
        $request->session()->regenerate();

        // ڕێکخستنی هۆڵی چالاک
        if ($user->current_gym_id) {
            GymScope::setActiveGymId($user->current_gym_id);
        }

        // تۆمارکردن لە ئۆدیت لۆگ (Audit Log)
        AuditLog::create([
            'gym_id' => $user->current_gym_id,
            'user_id' => $user->id,
            'action' => 'auth.login_success',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'new_values' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->intended(route('dashboard'))->with('success', "بەخێربێیت {$user->name}! چوونەژوورەوە بە سەرکەوتوویی ئەنجامدرا.");
    }

    /**
     * پیشاندانی فۆرمی سەلماندنی 2FA / Security PIN
     */
    public function show2fa()
    {
        $userId = session('auth.2fa.user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        return view('auth.verify_2fa', compact('user'));
    }

    /**
     * پشکنینی کۆدی 2FA / Security PIN
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:6',
        ], [
            'pin.required' => 'تکایە پینی نهێنی ٦ ژمارەیی بنووسە.',
            'pin.size' => 'پینی نهێنی دەبێت ڕێک ٦ ژمارە بێت.',
        ]);

        $userId = session('auth.2fa.user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        if (!Hash::check($request->pin, $user->security_pin) && $request->pin !== $user->security_pin) {
            return back()->withErrors([
                'pin' => 'کۆدی پینی 2FA نادروستە! تکایە دووبارە پینە دروستەکەت بنووسە.',
            ]);
        }

        $remember = session('auth.2fa.remember', false);
        session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($user->current_gym_id) {
            GymScope::setActiveGymId($user->current_gym_id);
        }

        AuditLog::create([
            'gym_id' => $user->current_gym_id,
            'user_id' => $user->id,
            'action' => 'auth.2fa_verified',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'new_values' => ['status' => '2FA verified successfully'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->intended(route('dashboard'))->with('success', "پشکنینی 2FA سەرکەوتوو بوو. بەخێربێیت {$user->name}!");
    }

    /**
     * دەرچوون لە سیستەم (Logout)
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            AuditLog::create([
                'gym_id' => $user->current_gym_id,
                'user_id' => $user->id,
                'action' => 'auth.logout',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'بە سەرکەوتوویی لە سیستەمەکە دەرچوویت.');
    }
}
