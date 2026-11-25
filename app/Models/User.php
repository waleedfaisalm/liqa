<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'specialty', 'bio', 'avatar_path', 'notifications_enabled', 'profile_public'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /** صلاحيات أقسام لوحة الإدارة (المالك يملكها كلها، والإعدادات له وحده) */
    public const PERMISSIONS = [
        'users' => 'إدارة المستخدمين',
        'stats' => 'لوحة الإحصاءات',
        'events' => 'إدارة الأحداث',
        'comments' => 'التعليقات والتقييمات',
        'reports' => 'البلاغات ورسائل التواصل',
        'broadcast' => 'الإشعار العام',
        'logs' => 'سجل الإجراءات',
        'export' => 'تصدير البيانات',
    ];

    /** التخصصات المتاحة (قائمة ثابتة) */
    public const SPECIALTIES = ['مطوّر', 'مصمم', 'كاتب محتوى', 'مسوّق', 'مونتير', 'مترجم', 'تخصص آخر'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'notifications_enabled' => 'boolean',
            'profile_public' => 'boolean',
            'is_admin' => 'boolean',
            'is_super_admin' => 'boolean',
            'suspended_at' => 'datetime',
            'verified_at' => 'datetime',
            'risk_flags' => 'array',
            'risk_cleared_at' => 'datetime',
            'join_restricted_until' => 'datetime',
            'last_seen_at' => 'datetime',
            'suspended_until' => 'datetime',
            'password' => 'hashed',
            'admin_permissions' => 'array',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
        ];
    }

    /** كاش داخل الطلب الواحد لمعرّفات الأحداث المحفوظة */
    protected ?array $savedIdsCache = null;

    // ---------- علاقات ----------

    public function savedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'saved_events')->withTimestamps();
    }

    public function hasSaved(Event $event): bool
    {
        $this->savedIdsCache ??= $this->savedEvents()->pluck('events.id')->all();

        return in_array($event->id, $this->savedIdsCache, true);
    }

    public function hostedEvents(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function eventRequests(): HasMany
    {
        return $this->hasMany(EventRequest::class);
    }

    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'ratee_id');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    // ---------- مساعدات ----------

    /** من ظهر خلال آخر 5 دقائق يُعدّ متصلًا */
    public const ONLINE_MINUTES = 5;

    /** حسابات مشبوهة: درجة خطورة متوسطة فأعلى ولم يعتمدها المشرف */
    public function scopeRisky($query)
    {
        return $query->where('is_admin', false)->whereNull('risk_cleared_at')->where('risk_score', '>=', 30);
    }

    /** مقيّد من الانضمام للأحداث بسبب الغياب */
    public function isJoinRestricted(): bool
    {
        return $this->join_restricted_until !== null && $this->join_restricted_until->isFuture();
    }

    public function scopeOnline($query)
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes(self::ONLINE_MINUTES));
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gte(now()->subMinutes(self::ONLINE_MINUTES));
    }

    /** موقوف حاليًا: إيقاف دائم، أو مؤقت لم تنتهِ مدته بعد */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null
            && ($this->suspended_until === null || $this->suspended_until->isFuture());
    }

    public function scopeSuspended($query)
    {
        return $query->whereNotNull('suspended_at')
            ->where(fn ($w) => $w->whereNull('suspended_until')->orWhere('suspended_until', '>', now()));
    }

    /** رسالة تُعرض للمستخدم الموقوف عند محاولة الدخول */
    public function suspensionMessage(): string
    {
        $text = __('تم إيقاف هذا الحساب من قِبل إدارة المنصة');

        if ($this->suspension_reason) {
            $text .= __(' — السبب: :r', ['r' => $this->suspension_reason]);
        }

        if ($this->suspended_until) {
            $text .= __(' — ينتهي الإيقاف في :d', ['d' => $this->suspended_until->translatedFormat('j F Y')]);
        }

        return $text.'.';
    }

    /** المالك: الحساب الأساسي المحمي، لا يوقفه ولا يخفض صلاحيته أحد */
    public function isOwner(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /** هل يملك المدير صلاحية قسم؟ (بلا قائمة محفوظة = كل الأقسام ما عدا الإعدادات) */
    public function hasPermission(string $key): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        if (! $this->isAdmin() || $key === 'settings') {
            return false;
        }

        if ($this->admin_permissions === null) {
            return array_key_exists($key, self::PERMISSIONS);
        }

        return in_array($key, $this->admin_permissions, true);
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret;
    }

    public function isOnboarded(): bool
    {
        return $this->onboarded_at !== null;
    }

    public function initial(): string
    {
        return mb_substr($this->name, 0, 1);
    }

    /** هل الحساب موثّق من الإدارة؟ */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /** روابط الأعمال المضافة: [مفتاح => [اسم العرض, الرابط]] */
    public function workLinks(): array
    {
        $out = [];
        foreach (['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'behance' => 'Behance', 'website' => 'الموقع الشخصي'] as $key => $label) {
            $url = $this->{'link_'.$key};
            if ($url) {
                $out[$key] = [$label, $url];
            }
        }

        return $out;
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        if (str_starts_with($this->avatar_path, 'db:')) {
            return route('avatar', ['user' => $this->id, 'v' => substr($this->avatar_path, 3)]);
        }

        return asset('storage/'.$this->avatar_path);
    }

    /** متوسط التقييم (رقم عشري) أو null لو مفيش تقييمات */
    public function averageRating(): ?float
    {
        $avg = $this->ratingsReceived()->avg('stars');

        return $avg === null ? null : round((float) $avg, 1);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->userNotifications()->whereNull('read_at')->count();
    }

    /** عدد اللقاءات اللي حضرها فعلاً (مقبول + الحدث خلص) */
    public function attendedCount(): int
    {
        return $this->eventRequests()
            ->where('status', EventRequest::ACCEPTED)
            ->whereHas('event', fn ($q) => $q->where('status', Event::PUBLISHED))
            ->get()
            ->filter(fn ($r) => $r->event->hasEnded())
            ->count();
    }
}
