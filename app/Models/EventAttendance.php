<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** حضور فعلي في الغرفة المباشرة: يُحسب بالثواني من نبضات الغرفة */
class EventAttendance extends Model
{
    /** أقل مدة (بالثواني) لاعتبار العضو حاضرًا واستحقاق الشهادة */
    public const THRESHOLD = 600;

    protected $fillable = ['event_id', 'user_id', 'seconds', 'first_seen_at', 'last_seen_at'];

    protected function casts(): array
    {
        return ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attended(): bool
    {
        return $this->seconds >= self::THRESHOLD;
    }

    public function minutes(): int
    {
        return (int) floor($this->seconds / 60);
    }

    /** رمز تحقق فريد للشهادة، يُنشأ عند أول طلب */
    public function ensureCode(): string
    {
        if ($this->certificate_code) {
            return $this->certificate_code;
        }

        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'LQ-';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                if ($i === 3) {
                    $code .= '-';
                }
            }
        } while (self::where('certificate_code', $code)->exists());

        $this->forceFill(['certificate_code' => $code])->save();

        return $code;
    }
}
