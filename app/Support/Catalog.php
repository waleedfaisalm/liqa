<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;

/**
 * القوائم القابلة للإدارة من لوحة الإدارة: مجالات الأحداث، التخصصات، الدول والمدن، ونصوص الصفحة الرئيسية.
 * تُقرأ من الإعدادات، وتعود للقيم الافتراضية في الكود إن لم تُعدَّل.
 */
class Catalog
{
    /** @return array<string,string> مفتاح => اسم المجال */
    public static function eventTypes(bool $activeOnly = true): array
    {
        $out = [];
        foreach (self::eventTypeRows() as $row) {
            if (! $activeOnly || $row['active']) {
                $out[$row['key']] = $row['label'];
            }
        }

        return $out;
    }

    /** @return list<array{key:string,label:string,active:bool}> */
    public static function eventTypeRows(): array
    {
        $saved = Setting::json('event_types');

        if (is_array($saved) && $saved) {
            return array_values(array_map(fn ($r) => [
                'key' => (string) $r['key'], 'label' => (string) $r['label'], 'active' => (bool) ($r['active'] ?? true),
            ], $saved));
        }

        return array_values(array_map(fn ($key, $label) => ['key' => $key, 'label' => $label, 'active' => true], array_keys(Event::TYPES), Event::TYPES));
    }

    public static function eventTypeLabel(?string $key): string
    {
        return self::eventTypes(false)[$key] ?? (Event::TYPES[$key] ?? (string) $key);
    }

    /** @return list<array{label:string,active:bool}> */
    public static function specialtyRows(): array
    {
        $saved = Setting::json('specialties');

        if (is_array($saved) && $saved) {
            return array_values(array_map(fn ($r) => ['label' => (string) $r['label'], 'active' => (bool) ($r['active'] ?? true)], $saved));
        }

        return array_map(fn ($l) => ['label' => $l, 'active' => true], User::SPECIALTIES);
    }

    /** @return list<string> */
    public static function specialties(bool $activeOnly = true): array
    {
        return array_values(array_map(fn ($r) => $r['label'], array_filter(self::specialtyRows(), fn ($r) => ! $activeOnly || $r['active'])));
    }

    /** @return array<string,list<string>> دولة => مدنها */
    public static function places(): array
    {
        $saved = Setting::json('places');

        return is_array($saved) && $saved ? $saved : config('places');
    }

    /** نصوص الصفحة الرئيسية بعد دمج تعديلات المالك فوق الافتراضية */
    public static function landing(): array
    {
        $defaults = config('landing');
        $saved = Setting::json('landing');

        return is_array($saved) ? self::merge($defaults, $saved) : $defaults;
    }

    private static function merge(array $defaults, array $saved): array
    {
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $saved)) {
                continue;
            }
            if (is_array($value)) {
                $defaults[$key] = is_array($saved[$key]) ? self::merge($value, $saved[$key]) : $value;
            } elseif (is_string($saved[$key]) && trim($saved[$key]) !== '') {
                $defaults[$key] = $saved[$key];
            }
        }

        return $defaults;
    }
}
