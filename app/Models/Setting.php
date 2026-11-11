<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** القيم الافتراضية لإعدادات المنصة */
    public const DEFAULTS = [
        'registration_enabled' => '1',
        'event_creation_enabled' => '1',
        'announcement' => '',
        'privacy_enabled' => '1',
        'terms_enabled' => '1',
        'privacy_content' => '',
        'terms_content' => '',
        'site_name' => 'لقاء',
        'site_tagline' => 'منصة لقاءات الفريلانسر المهنية · تعارف، لقاء، ونموّ مشترك',
        'support_email' => '',
        'social_x' => '',
        'social_instagram' => '',
        'social_linkedin' => '',
    ];

    private static ?array $cache = null;

    private static function all_(): array
    {
        if (self::$cache === null) {
            // كاش ملفّي محلي (بدون رحلة شبكة لقاعدة البيانات في كل طلب)، ويُمسح عند أي حفظ
            try {
                self::$cache = \Illuminate\Support\Facades\Cache::store('file')->remember('liqa.settings', 60, function () {
                    return static::query()->pluck('value', 'key')->all();
                });
            } catch (\Throwable) {
                self::$cache = [];
            }
        }

        return self::$cache;
    }

    public static function get(string $key): ?string
    {
        $all = self::all_();

        return array_key_exists($key, $all) ? $all[$key] : (self::DEFAULTS[$key] ?? null);
    }

    /** قيمة مخزّنة كـ JSON (أو null إن لم تُحفظ) */
    public static function json(string $key): mixed
    {
        $raw = self::get($key);

        return $raw === null || $raw === '' ? null : json_decode($raw, true);
    }

    public static function putJson(string $key, mixed $value): void
    {
        self::put($key, $value === null ? '' : json_encode($value, JSON_UNESCAPED_UNICODE));
    }

    /** اسم المنصة وبياناتها العامة */
    public static function site(string $key): string
    {
        $v = trim((string) self::get('site_'.$key));

        return $v !== '' ? $v : (string) (self::DEFAULTS['site_'.$key] ?? '');
    }

    public static function updatedAt(string $key): \Illuminate\Support\Carbon
    {
        try {
            $row = static::find($key);
        } catch (\Throwable) {
            $row = null;
        }

        return $row?->updated_at ?? now();
    }

    public static function enabled(string $key): bool
    {
        return self::get($key) === '1';
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
        try {
            \Illuminate\Support\Facades\Cache::store('file')->forget('liqa.settings');
        } catch (\Throwable) {
        }
    }
}
