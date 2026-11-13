<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** حالة دخول عضو إلى الغرفة المباشرة: انتظار / مقبول / مرفوض / مُخرَج */
class EventRoomEntry extends Model
{
    public const WAITING = 'waiting';

    public const ADMITTED = 'admitted';

    public const DENIED = 'denied';

    public const REMOVED = 'removed';

    protected $fillable = ['event_id', 'user_id', 'status', 'is_cohost', 'via_link'];

    protected function casts(): array
    {
        return ['is_cohost' => 'boolean', 'via_link' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
