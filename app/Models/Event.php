<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    public const PUBLISHED = 'published';
    public const CANCELLED = 'cancelled';

    public const TYPES = [
        'programming' => 'برمجة',
        'design' => 'تصميم',
        'content' => 'كتابة محتوى',
        'marketing' => 'تسويق',
        'business' => 'بيزنس / فريلانس عام',
        'other' => 'مجال آخر',
    ];

    public const MODES = [
        'in_person' => 'حضوري',
        'online' => 'عن بُعد',
    ];

    public const DURATIONS = [
        60 => 'ساعة',
        90 => 'ساعة ونصف',
        120 => 'ساعتين',
        180 => '٣ ساعات',
        240 => '٤ ساعات',
    ];

    protected $fillable = [
        'title', 'type', 'mode', 'country', 'city', 'location', 'starts_at',
        'duration_minutes', 'capacity', 'description',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    // ---------- علاقات ----------

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(EventRequest::class);
    }

    public function acceptedRequests(): HasMany
    {
        return $this->hasMany(EventRequest::class)->where('status', EventRequest::ACCEPTED);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(EventComment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    // ---------- Scopes ----------

    /** أحداث منشورة ولسه ما بدأتش */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', self::PUBLISHED)->where('starts_at', '>=', now());
    }

    // ---------- حالة الحدث ----------

    public function endsAt(): Carbon
    {
        return $this->starts_at->copy()->addMinutes($this->duration_minutes);
    }

    public function hasEnded(): bool
    {
        return $this->endsAt()->isPast();
    }

    /** الغرفة المباشرة تُفتح قبل الموعد بنصف ساعة وتُغلق بعد انتهاء الحدث بثلاث ساعات */
    public const ROOM_OPENS_BEFORE_MINUTES = 30;

    public const ROOM_CLOSES_AFTER_HOURS = 3;

    public function roomOpensAt(): Carbon
    {
        return $this->starts_at->copy()->subMinutes(self::ROOM_OPENS_BEFORE_MINUTES);
    }

    public function roomIsOpen(): bool
    {
        return ! $this->isCancelled()
            && now()->gte($this->roomOpensAt())
            && now()->lte($this->endsAt()->copy()->addHours(self::ROOM_CLOSES_AFTER_HOURS));
    }

    public function roomName(): string
    {
        return 'liqa-event-'.$this->id;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::CANCELLED;
    }

    public function seatsTaken(): int
    {
        return $this->accepted_requests_count ?? $this->acceptedRequests()->count();
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - $this->seatsTaken());
    }

    public function isFull(): bool
    {
        return $this->seatsLeft() === 0;
    }

    public function isHostedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    /** طلب المستخدم على الحدث ده (لو موجود) */
    public function requestOf(User $user): ?EventRequest
    {
        if ($this->relationLoaded('requests')) {
            return $this->requests->firstWhere('user_id', $user->id);
        }

        return $this->requests()->where('user_id', $user->id)->first();
    }

    /** المنظّم أو عضو مقبول */
    public function isParticipant(User $user): bool
    {
        return $this->isHostedBy($user)
            || $this->acceptedRequests()->where('user_id', $user->id)->exists();
    }

    /** كل المشاركين: المنظّم + المقبولين */
    public function participants(): Collection
    {
        return User::query()
            ->where('id', $this->user_id)
            ->orWhereIn('id', $this->acceptedRequests()->select('user_id'))
            ->get();
    }

    /**
     * عند توفّر مقاعد: ينقل أقدم المنتظرين إلى طلبات قيد المراجعة لدى المنظّم
     * (القبول النهائي يبقى بيد المنظّم). يعيد عدد من تم نقلهم.
     */
    public function promoteWaitlist(): int
    {
        if ($this->isCancelled() || $this->hasEnded()) {
            return 0;
        }

        $left = max(0, $this->capacity - $this->acceptedRequests()->count());

        if ($left === 0) {
            return 0;
        }

        $next = $this->requests()
            ->where('status', EventRequest::WAITLISTED)
            ->orderBy('id')
            ->with('user')
            ->limit($left)
            ->get();

        $host = $this->host ?? User::find($this->user_id);

        foreach ($next as $request) {
            $request->update(['status' => EventRequest::PENDING]);

            UserNotification::send(
                $request->user,
                'waitlist_promoted',
                'تحرّر مقعد في حدث «'.$this->title.'»، وانتقل طلبك إلى مراجعة المنظّم.',
                route('events.show', $this),
            );

            UserNotification::send(
                $host,
                'request_new',
                $request->user->name.' انتقل من قائمة الانتظار إلى طلبات الانضمام في حدث «'.$this->title.'».',
                route('events.show', $this),
            );
        }

        return $next->count();
    }

    /** هل rater يقدر يقيّم ratee عن الحدث ده؟ */
    public function canRate(User $rater, User $ratee): bool
    {
        if ($rater->id === $ratee->id || $this->isCancelled() || ! $this->hasEnded()) {
            return false;
        }

        if (! $this->isParticipant($rater) || ! $this->isParticipant($ratee)) {
            return false;
        }

        return ! $this->ratings()
            ->where('rater_id', $rater->id)
            ->where('ratee_id', $ratee->id)
            ->exists();
    }

    // ---------- عرض ----------

    /** المدينة والمكان في سطر واحد */
    public function placeLabel(): string
    {
        $area = implode('، ', array_filter([$this->city, $this->country]));

        return $area !== '' ? $area.' · '.$this->location : $this->location;
    }

    /** المكان للعرض في البطاقات: لا نُظهر رابط الاجتماع عن بُعد خارج صفحة اللقاء */
    public function cardPlaceLabel(): string
    {
        if ($this->mode === 'online') {
            $area = implode('، ', array_filter([$this->city, $this->country]));

            return $area !== '' ? 'عن بُعد · '.$area : 'لقاء عن بُعد';
        }

        return $this->placeLabel();
    }

    public function typeLabel(): string
    {
        return \App\Support\Catalog::eventTypeLabel($this->type);
    }

    public function modeLabel(): string
    {
        return self::MODES[$this->mode] ?? $this->mode;
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            'programming' => 'liqa-badge--sky',
            'design' => 'liqa-badge--pink',
            'marketing' => 'liqa-badge--green',
            default => '',
        };
    }

    public function whenLabel(): string
    {
        return $this->starts_at->translatedFormat('l j F · g:i A');
    }

    public function durationLabel(): string
    {
        return self::DURATIONS[$this->duration_minutes] ?? $this->duration_minutes.' دقيقة';
    }
}
