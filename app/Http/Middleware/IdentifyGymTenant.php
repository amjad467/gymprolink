<?php

namespace App\Http\Middleware;

use App\Models\Gym;
use App\Models\GymShop;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class IdentifyGymTenant
{
    /**
     * پشکنینی هۆڵی دیاریکراو و دڵنیابوونەوە لە دەسەڵاتی بەکارهێنەر بەسەر ئەو هۆڵەدا
     */
    public function handle(Request $request, Closure $next): Response
    {
        $gymId = null;

        // ١. پشکنینی Header لە API یان ئامێری سکەنەر
        if ($request->hasHeader('X-Gym-Id')) {
            $gymId = (int) $request->header('X-Gym-Id');
        } 
        // ٢. پشکنینی پارامیتەری ڕێڕەو (Route parameter) ئەگەر هۆڵ یان شۆپ بوو
        elseif ($request->route('gym')) {
            $routeGym = $request->route('gym');
            $gymId = $routeGym instanceof Gym ? $routeGym->id : (int) $routeGym;
        } elseif ($request->route('shop')) {
            $routeShop = $request->route('shop');
            $gymId = $routeShop instanceof GymShop ? $routeShop->gym_id : GymShop::withoutGlobalScopes()->find($routeShop)?->gym_id;
        }
        // ٣. پشکنینی Session
        elseif (Session::has('active_gym_id')) {
            $gymId = (int) Session::get('active_gym_id');
        }

        // پشکنینی ئاسایش بۆ بەکارهێنەری چۆتەژوورەوە
        if (auth()->check()) {
            $user = auth()->user();

            // ئەگەر Super Admin نەبێت، دەبێت پشکنین بکرێت ئایا دەسەڵاتی ئەو هۆڵەی هەیە
            if (!$user->is_super_admin) {
                // ئەگەر هۆڵ دیاری نەکرابوو، هۆڵی سەرەکی خۆی دادەنێین
                if (!$gymId) {
                    $gymId = $user->current_gym_id ?? $user->gyms()->first()?->id;
                }

                // پشکنینی IDOR: ئەگەر هەوڵ بدات بچێتە هۆڵێک کە ئەندام یان کارمەندی نییە
                if ($gymId && !$user->gyms()->where('gym_id', $gymId)->where('gym_user.is_active', true)->exists()) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'مۆڵەتی دەستگەیشتنت بە داتای ئەم هۆڵە نییە (Unauthorized Gym Access).',
                        ], 403);
                    }
                    abort(403, 'مۆڵەتی دەستگەیشتنت بە داتای ئەم هۆڵە نییە.');
                }
            }

            // نوێکردنەوەی Session و دۆخی هەنووکەیی
            if ($gymId) {
                Session::put('active_gym_id', $gymId);
                if ($user->current_gym_id !== $gymId) {
                    $user->current_gym_id = $gymId;
                    $user->saveQuietly();
                }
            }
        }

        return $next($request);
    }
}
