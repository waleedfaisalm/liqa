<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    /** وسوم جاهزة للتقييم */
    public const TAGS = ['ودود', 'محترم', 'متعاون', 'منظّم', 'ملتزم بالموعد', 'حديثه مفيد', 'ملهم', 'صبور', 'منفتح على الأفكار', 'أتاح لي فرصًا'];

    protected $fillable = ['event_id', 'rater_id', 'ratee_id', 'stars', 'tags', 'comment'];

    protected function casts(): array
    {
        return ['tags' => 'array'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }
}
