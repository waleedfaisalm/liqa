<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRequest extends Model
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';
    public const WAITLISTED = 'waitlisted';

    protected $fillable = ['event_id', 'user_id', 'status'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
