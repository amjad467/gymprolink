<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Session;

class GymScope implements Scope
{
    /**
     * جێبەجێکردنی فلتەری هۆڵ بەسەر هەموو Query ـەکاندا بە شێوەی خۆکار
     */
    public function apply(Builder $builder, Model $model): void
    {
        // ئەگەر لە دۆخی Global تێپەڕین بووین، فلتەر مەکە (بۆ Super Admin لە کاتی ڕاپۆرتی گشتی)
        if (app()->bound('ignore_gym_scope') && app('ignore_gym_scope')) {
            return;
        }

        $activeGymId = static::getActiveGymId();

        if ($activeGymId) {
            $builder->where($model->getTable() . '.gym_id', $activeGymId);
        }
    }

    /**
     * بەدەستهێنانی ناسنامەی هۆڵی چالاک لە Session یان Header یان بەکارهێنەر
     */
    public static function getActiveGymId(): ?int
    {
        // ١. ئەگەر لە ڕێگەی Header ـی داواکارییەوە دیاریکرابێت (بۆ API و ئامێرەکانی دەرگا)
        if (request()->hasHeader('X-Gym-Id')) {
            return (int) request()->header('X-Gym-Id');
        }

        // ٢. ئەگەر لە Session تۆمارکرابێت (بۆ داشبۆردی کارگێڕی)
        if (Session::has('active_gym_id')) {
            return (int) Session::get('active_gym_id');
        }

        // ٣. ئەگەر بەکارهێنەر چۆتە ژوورەوە و هۆڵی هەنووکەیی هەبێت
        if (auth()->check() && auth()->user()->current_gym_id) {
            return (int) auth()->user()->current_gym_id;
        }

        return null;
    }

    public static function setActiveGymId(?int $gymId): void
    {
        Session::put('active_gym_id', $gymId);
    }
}
