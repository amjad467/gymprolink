<?php

namespace App\Traits;

use App\Models\Gym;
use App\Scopes\GymScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToGym
{
    /**
     * بەستنەوەی مۆدێل بە Global Scope ـی تایبەت بە هۆڵ
     */
    protected static function bootBelongsToGym(): void
    {
        static::addGlobalScope(new GymScope);

        // لە کاتی دروستکردنی تۆماری نوێ، خۆکارانە gym_id پڕ دەکاتەوە ئەگەر دانەنرابێت
        static::creating(function ($model) {
            if (empty($model->gym_id)) {
                $model->gym_id = GymScope::getActiveGymId();
            }
        });
    }

    /**
     * پەیوەندی بە مۆدێلی هۆڵ (Gym)
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }
}
