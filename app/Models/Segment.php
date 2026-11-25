<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** شريحة محفوظة من الأعضاء بشروط قابلة لإعادة الاستخدام (للإشعارات الموجّهة) */
class Segment extends Model
{
    protected $fillable = ['name', 'filters', 'created_by', 'last_sent_at'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'last_sent_at' => 'datetime'];
    }

    /** الأعضاء المطابقون (بلا مدراء ولا موقوفين) */
    public function members(): Builder
    {
        $f = $this->filters ?? [];
        $q = User::query()->where('is_admin', false)->whereNull('suspended_at');

        if (! empty($f['city'])) {
            $q->whereRaw('lower(city) = ?', [mb_strtolower(trim($f['city']))]);
        }
        if (! empty($f['specialty'])) {
            $q->where('specialty', $f['specialty']);
        }
        if (! empty($f['verified'])) {
            $q->whereNotNull('verified_at');
        }
        if (! empty($f['joined_within_days'])) {
            $q->where('created_at', '>=', now()->subDays((int) $f['joined_within_days']));
        }
        if (! empty($f['active_within_days'])) {
            $q->where('last_seen_at', '>=', now()->subDays((int) $f['active_within_days']));
        }
        if (! empty($f['inactive_days'])) {
            $cut = now()->subDays((int) $f['inactive_days']);
            $q->where(fn ($w) => $w->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $cut));
        }
        if (! empty($f['attended_min'])) {
            $n = (int) $f['attended_min'];
            $q->whereIn('id', fn ($s) => $s->from('event_attendances')->select('user_id')
                ->where('seconds', '>=', EventAttendance::THRESHOLD)->groupBy('user_id')->havingRaw('count(*) >= ?', [$n]));
        }
        if (! empty($f['never_attended'])) {
            $q->whereNotIn('id', fn ($s) => $s->from('event_attendances')->select('user_id')->where('seconds', '>=', EventAttendance::THRESHOLD));
        }

        return $q;
    }

    /** وصف مقروء للشروط */
    public function describe(): string
    {
        $f = $this->filters ?? [];
        $p = [];
        if (! empty($f['city'])) $p[] = 'المدينة: '.$f['city'];
        if (! empty($f['specialty'])) $p[] = 'التخصص: '.$f['specialty'];
        if (! empty($f['verified'])) $p[] = 'موثّقون';
        if (! empty($f['joined_within_days'])) $p[] = 'انضموا خلال '.$f['joined_within_days'].' يومًا';
        if (! empty($f['active_within_days'])) $p[] = 'نشطون خلال '.$f['active_within_days'].' يومًا';
        if (! empty($f['inactive_days'])) $p[] = 'غائبون '.$f['inactive_days'].' يومًا فأكثر';
        if (! empty($f['attended_min'])) $p[] = 'حضروا '.$f['attended_min'].' لقاءات فأكثر';
        if (! empty($f['never_attended'])) $p[] = 'لم يحضروا أي لقاء';

        return $p ? implode(' · ', $p) : 'كل الأعضاء';
    }
}
