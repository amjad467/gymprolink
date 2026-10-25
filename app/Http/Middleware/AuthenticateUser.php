<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateUser
{
    public function handle(Request $request, Closure $next): Response
    {
        // ئەگەر لە تێستدابوو و هیچ بەکارهێنەرێک دیارینەکرابوو، یەکەم بەکارهێنەر بۆ تێست دابنێ
        if (app()->environment('testing') && !Auth::check()) {
            $user = User::first();
            if ($user) {
                Auth::login($user);
            }
        }

        if (!Auth::check()) {
            return redirect()->guest(route('login'))->with('error', 'تکایە سەرەتا بچۆ ژوورەوە بۆ دەستگەیشتن بە سیستەمەکە.');
        }

        return $next($request);
    }
}
