<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => "مۆڵەتی پێویستت نییە بۆ ئەم کردارە ({$permission}).",
            ], 403);
        }

        return response()->view('errors.403', [
            'permission' => $permission,
            'message' => "ببوورە! مۆڵەتی پێویستت نییە بۆ دەستگەیشتن بەم بەشە یان ئەنجامدانی ئەم کردارە ({$permission}).",
        ], 403);
    }
}
