<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    /** أنواع تنبيهات الإدارة (تظهر في لوحة الإدارة) */
    public const ADMIN_TYPES = ['report_urgent', 'risk_alert'];

    protected $fillable = ['user_id', 'type', 'message', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /** إنشاء إشعار لمستخدم (بيتخطى اللي قافل الإشعارات) */
    public static function send(User $user, string $type, string $message, ?string $url = null): void
    {
        if (! $user->notifications_enabled) {
            return;
        }

        static::create([
            'user_id' => $user->id,
            'type' => $type,
            'message' => $message,
            'url' => $url,
        ]);
    }
}
