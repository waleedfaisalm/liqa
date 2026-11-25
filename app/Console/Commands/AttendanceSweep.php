<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\Automation;
use App\Support\Reliability;
use Illuminate\Console\Command;

/** فحص دوري: تسجيل الغياب وإنذاره، تنبيه ضعف التسجيل، واقتراح مقاعد إضافية */
class AttendanceSweep extends Command
{
    protected $signature = 'liqa:attendance-sweep';

    protected $description = 'معالجة الغياب وتنبيهات التسجيل واقتراحات المقاعد';

    public function handle(): int
    {
        Reliability::flush();

        $a = Automation::on('auto_absence') ? $this->absences() : 0;
        $l = Automation::on('auto_low_reg') ? $this->lowRegistration() : 0;
        $s = $this->seatHints();

        $this->info("إنذارات غياب: {$a} — تنبيهات تسجيل منخفض: {$l} — اقتراحات مقاعد: {$s}");

        return self::SUCCESS;
    }

    private function admins()
    {
        return User::where('is_admin', true)->get();
    }

    private function absences(): int
    {
        $count = 0;
        $threshold = max(2, Automation::int('auto_absence_strikes'));

        Event::where('status', Event::PUBLISHED)
            ->whereNull('absence_processed_at')
            ->where('starts_at', '<', now())
            ->where('starts_at', '>=', now()->subDays(5))
            ->get()
            ->filter(fn (Event $e) => $e->endsAt()->copy()->addHours(Event::ROOM_CLOSES_AFTER_HOURS)->isPast())
            ->each(function (Event $event) use (&$count, $threshold) {
                $tracked = $event->attendances()->where('seconds', '>=', EventAttendance::THRESHOLD)->exists();
                $event->forceFill(['absence_processed_at' => now()])->save();

                if (! $tracked) {
                    return;
                }

                $present = $event->attendances()->where('seconds', '>=', EventAttendance::THRESHOLD)->pluck('user_id')->all();

                EventRequest::where('event_id', $event->id)->where('status', EventRequest::ACCEPTED)
                    ->whereNotIn('user_id', $present)->with('user')->get()
                    ->each(function ($req) use ($event, $threshold, &$count) {
                        $u = $req->user;
                        if (! $u) {
                            return;
                        }

                        $strikes = Reliability::stats($u)['recent_missed'];

                        $msg = match (true) {
                            $strikes >= $threshold => 'تنبيه أخير: غبت '.$strikes.' مرات خلال 90 يومًا (آخرها «'.$event->title.'»). قد تقرر الإدارة تقييد انضمامك للأحداث لفترة.',
                            $strikes === 2 => 'تنبيه: هذا غيابك الثاني خلال 90 يومًا (حدث «'.$event->title.'»). تكرار الغياب قد يؤدي إلى تقييد الانضمام.',
                            default => 'لاحظنا غيابك عن «'.$event->title.'» رغم تسجيلك. إن تعذّر حضورك مستقبلًا فألغِ تسجيلك مبكرًا ليستفيد غيرك.',
                        };

                        UserNotification::send($u, 'absence_notice', $msg, route('events.show', $event));
                        $count++;

                        if ($strikes >= $threshold && ! $u->isJoinRestricted()) {
                            $url = route('admin.users.show', $u);
                            foreach ($this->admins() as $admin) {
                                $dup = $admin->userNotifications()->where('type', 'noshow_alert')->where('url', $url)->where('created_at', '>=', now()->subDays(30))->exists();
                                if (! $dup) {
                                    UserNotification::send($admin, 'noshow_alert', 'العضو «'.$u->name.'» غاب '.$strikes.' مرات خلال 90 يومًا. راجع ملفه وقرر تقييد الانضمام.', $url);
                                }
                            }
                        }
                    });
            });

        return $count;
    }

    private function lowRegistration(): int
    {
        $count = 0;
        $days = max(1, Automation::int('auto_low_reg_days'));
        $percent = max(5, Automation::int('auto_low_reg_percent'));

        Event::where('status', Event::PUBLISHED)
            ->whereNull('low_reg_alerted_at')
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addDays($days))
            ->withCount('acceptedRequests')
            ->with('host')
            ->get()
            ->each(function (Event $event) use (&$count, $percent) {
                $limit = (int) ceil($event->capacity * $percent / 100);
                if ($event->accepted_requests_count >= $limit) {
                    return;
                }

                $event->forceFill(['low_reg_alerted_at' => now()])->save();
                $count++;

                if ($event->host) {
                    UserNotification::send($event->host, 'event_low_registration', 'حدثك «'.$event->title.'» يقترب وسجّل فيه '.$event->accepted_requests_count.' فقط من '.$event->capacity.' مقعدًا. شارك رابطه في مجموعاتك لزيادة التسجيل.', route('events.show', $event));
                }

                foreach ($this->admins() as $admin) {
                    UserNotification::send($admin, 'low_registration', 'تسجيل منخفض في «'.$event->title.'» ('.$event->accepted_requests_count.'/'.$event->capacity.') وموعده '.$event->whenLabel().'. فكّر في الترويج له.', route('admin.events'));
                }
            });

        return $count;
    }

    private function seatHints(): int
    {
        $count = 0;

        Event::where('status', Event::PUBLISHED)
            ->whereNull('seats_hint_sent_at')
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addHours(48))
            ->withCount('acceptedRequests')
            ->with('host')
            ->get()
            ->each(function (Event $event) use (&$count) {
                $extra = Reliability::extraSeats($event);
                if ($extra < 1 || ! $event->host) {
                    return;
                }

                $event->forceFill(['seats_hint_sent_at' => now()])->save();
                UserNotification::send($event->host, 'seats_hint', 'حدث «'.$event->title.'» ممتلئ وله قائمة انتظار، ونتوقع غياب بعض المسجلين. يمكنك رفع السعة بنحو '.$extra.' مقعد.', route('events.edit', $event));
                $count++;
            });

        return $count;
    }
}
