<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\UserNotification;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'liqa:send-reminders';

    protected $description = 'يرسل تذكيرًا قبل الحدث بـ 24 ساعة، وإشعار «قيّم المشاركين» بعد انتهائه';

    public function handle(): int
    {
        $reminded = $this->sendUpcomingReminders();
        $prompted = $this->sendRatePrompts();

        $this->info("تذكيرات الأحداث: {$reminded} — إشعارات التقييم: {$prompted}");

        return self::SUCCESS;
    }

    /** تذكير بالأحداث التي تبدأ خلال 24 ساعة */
    private function sendUpcomingReminders(): int
    {
        $count = 0;

        Event::where('status', Event::PUBLISHED)
            ->whereNull('reminder_sent_at')
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addHours(24))
            ->with('host')
            ->get()
            ->each(function (Event $event) use (&$count) {
                $participants = $event->participants();

                // لا تذكير لحدث لم يقبل فيه المنظّم أحدًا بعد (سيُرسل عند القبول إن بقي ضمن 24 ساعة)
                if ($participants->count() < 2) {
                    return;
                }

                $participants->each(fn ($user) => UserNotification::send(
                    $user,
                    'event_reminder',
                    'تذكير: يبدأ حدث «'.$event->title.'» '.$event->whenLabel().'.',
                    route('events.show', $event),
                ));

                Event::whereKey($event->id)->update(['reminder_sent_at' => now()]);
                $count++;
            });

        return $count;
    }

    /** دعوة المشاركين لتقييم بعضهم بعد انتهاء الحدث (خلال آخر 7 أيام) */
    private function sendRatePrompts(): int
    {
        $count = 0;

        Event::where('status', Event::PUBLISHED)
            ->whereNull('rate_prompt_sent_at')
            ->where('starts_at', '<=', now())
            ->where('starts_at', '>=', now()->subDays(8))
            ->with('host')
            ->get()
            ->filter(fn (Event $event) => $event->hasEnded() && $event->endsAt()->greaterThan(now()->subDays(7)))
            ->each(function (Event $event) use (&$count) {
                $participants = $event->participants();

                if ($participants->count() >= 2) {
                    $participants->each(fn ($user) => UserNotification::send(
                        $user,
                        'rate_now',
                        'انتهى حدث «'.$event->title.'». قيّم المشاركين الآن وساهم في بناء مجتمع موثوق.',
                        route('events.show', $event).'#ratings',
                    ));
                    $count++;
                }

                Event::whereKey($event->id)->update(['rate_prompt_sent_at' => now()]);
            });

        return $count;
    }
}
