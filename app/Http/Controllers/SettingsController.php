<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Gym;
use App\Scopes\GymScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    public function index()
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $gym = Gym::findOrFail($activeGymId);

        $settings = $gym->settings ?? [];
        $currentFont = $settings['font_family'] ?? 'Vazirmatn';
        $currentFontSize = $settings['font_size'] ?? 'normal';
        $currentTheme = $settings['theme'] ?? 'dark';
        $currentLang = Session::get('app_locale', $settings['language'] ?? 'ku');

        return view('settings.index', compact('gym', 'settings', 'currentFont', 'currentFontSize', 'currentTheme', 'currentLang'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'font_family' => 'required|in:Vazirmatn,Noto Kufi Arabic,Tahoma,Inter',
            'font_size' => 'required|in:small,normal,large,xlarge',
            'theme' => 'required|in:dark,light',
            'language' => 'required|in:ku,ar',
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
        ]);

        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $gym = Gym::findOrFail($activeGymId);

        $settings = $gym->settings ?? [];
        $settings['slogan'] = $request->slogan;
        $settings['font_family'] = $request->font_family;
        $settings['font_size'] = $request->font_size;
        $settings['theme'] = $request->theme;
        $settings['language'] = $request->language;

        // ئەگەر وێنە/لۆگۆی نوێ بارکرابوو
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $fileName = 'gym_' . $gym->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destDir = public_path('uploads/logos');
            if (!File::isDirectory($destDir)) {
                File::makeDirectory($destDir, 0755, true);
            }
            $file->move($destDir, $fileName);
            $gym->logo_url = '/uploads/logos/' . $fileName;
        } elseif ($request->filled('logo_preset')) {
            $gym->logo_url = $request->logo_preset;
        }

        $gym->name = $request->name;
        $gym->phone = $request->phone;
        $gym->email = $request->email;
        $gym->address = $request->address;
        $gym->settings = $settings;
        $gym->save();

        // نوێکردنەوەی Session
        Session::put('app_locale', $request->language);
        Session::put('app_theme', $request->theme);
        Session::put('app_font', $request->font_family);
        Session::put('app_font_size', $request->font_size);

        // لۆگی سکیوریتی
        AuditLog::create([
            'gym_id' => $gym->id,
            'user_id' => auth()->id() ?? 1,
            'action' => 'GYM_SETTINGS_UPDATED',
            'auditable_type' => Gym::class,
            'auditable_id' => $gym->id,
            'new_values' => $settings,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'ڕێکخستنەکانی سیستەم و هۆڵ بە سەرکەوتوویی نوێکرانەوە.');
    }

    /**
     * گۆڕینی خێرای زمان (کوردی / عەرەبی) لە هیدەر
     */
    public function setLocale(Request $request, string $locale)
    {
        if (in_array($locale, ['ku', 'ar'])) {
            Session::put('app_locale', $locale);
            app()->setLocale($locale);
        }
        return back();
    }

    /**
     * گۆڕینی خێرای مۆدی شەو و ڕۆژ لە هیدەر
     */
    public function toggleTheme(Request $request)
    {
        $current = Session::get('app_theme', 'dark');
        $newTheme = ($current === 'dark') ? 'light' : 'dark';
        Session::put('app_theme', $newTheme);

        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $gym = Gym::find($activeGymId);
        if ($gym) {
            $settings = $gym->settings ?? [];
            $settings['theme'] = $newTheme;
            $gym->settings = $settings;
            $gym->saveQuietly();
        }

        return response()->json([
            'success' => true,
            'theme' => $newTheme,
        ]);
    }
}
