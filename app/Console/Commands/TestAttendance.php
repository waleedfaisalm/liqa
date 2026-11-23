<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventAttendance;
use Illuminate\Console\Command;

/** أمر اختبار مؤقت: يُنهي حدثًا ويمنح المقبولين حضورًا فعليًا لتجربة الشهادة. احذف المتغير بعد التجربة. */
class TestAttendance extends Command
{
    protected $signature = 'liqa:test-attendance {event : رقم الحدث}';

    protected $description = 'اختبار: إنهاء حدث ومنح المقبولين حضورًا لتجربة قائمة الحاضرين والشهادة';

    public function handle(): int
    {
        $event = Event::find((int) $this->argument('event'));

        if (! $event) {
            $this->warn('الحدث غير موجود.');

            return self::SUCCESS;
        }

        // يجعل الحدث منتهيًا (يبدأ قبل مدته + 10 دقائق)
        if (! $event->hasEnded()) {
            $event->starts_at = now()->subMinutes($event->duration_minutes + 10);
            $event->save();
        }

        $count = 0;
        foreach ($event->acceptedRequests()->get() as $req) {
            $a = EventAttendance::firstOrNew(['event_id' => $event->id, 'user_id' => $req->user_id]);
            if ($a->seconds < EventAttendance::THRESHOLD) {
                $a->seconds = 1500;
                $a->first_seen_at ??= $event->starts_at;
                $a->last_seen_at = $event->endsAt();
                $a->save();
                $count++;
            }
        }

        $this->info("الحدث #{$event->id} منتهٍ الآن، وسُجّل حضور {$count} مشاركين.");

        return self::SUCCESS;
    }
}
