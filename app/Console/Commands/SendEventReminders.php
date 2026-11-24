<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\UserNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command
{
    protected $signature = 'liqa:send-reminders';

    protected $description = 'يرسل تذكيرًا قبل الحدث بـ 24 ساعة، وإشعار «قيّم المشاركين» بعد انتهائه';

    public function handle(): int
    {
        $reminded = $this->sendUpcomingReminders();
        $prompted = $this->sendRatePrompts();

        try {
            $this->call('liqa:reactivate');
        } catch (\Throwable $e) {
            Log::warning('reactivate failed: '.$e->getMessage());
        }

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

                $participants->each(function ($user) use ($event) {
                    UserNotification::send(
                        $user,
                        'event_reminder',
                        'تذكير: يبدأ حدث «'.$event->title.'» '.$event->whenLabel().'.',
                        route('events.show', $event),
                    );
                    $this->mailReminder($user, $event);
                });

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
            ->filter(fn (Event $event) => $event->hasEnded()
                && $event->endsAt()->lessThanOrEqualTo(now()->subMinutes(max(0, \App\Support\Automation::int('auto_rating_delay'))))
                && $event->endsAt()->greaterThan(now()->subDays(7)))
            ->each(function (Event $event) use (&$count) {
                $participants = $event->participants();

                if ($participants->count() >= 2) {
                    $participants->each(function ($user) use ($event) {
                        UserNotification::send(
                            $user,
                            'rate_now',
                            'انتهى حدث «'.$event->title.'». قيّم المشاركين الآن وساهم في بناء مجتمع موثوق.',
                            route('events.show', $event).'#ratings',
                        );
                        $this->mailRatePrompt($user, $event);
                    });
                    $count++;
                }

                Event::whereKey($event->id)->update(['rate_prompt_sent_at' => now()]);
            });

        return $count;
    }

    /** بريد طلب التقييم (إن كانت إشعارات العضو مفعّلة) */
    private function mailRatePrompt($user, Event $event): void
    {
        if (! $user->notifications_enabled || ! $user->email) {
            return;
        }

        try {
            Mail::send('emails.action', [
                'heading' => 'كيف كان لقاء «'.$event->title.'»؟',
                'lines' => ['شكرًا لمشاركتك. تقييمك لزملائك في اللقاء يساعد على بناء مجتمع موثوق، ولا يستغرق دقيقة.'],
                'button' => 'قيّم المشاركين',
                'url' => route('events.show', $event).'#ratings',
                'footnote' => 'يمكنك إيقاف هذه الرسائل من إعدادات الإشعارات في حسابك.',
            ], fn ($m) => $m->to($user->email)->subject('قيّم لقاء «'.$event->title.'»'));
        } catch (\Throwable $e) {
            Log::warning('rate prompt mail failed: '.$e->getMessage());
        }
    }

    /** عبارة الموعد في عنوان البريد بحسب القرب الفعلي (قريبًا / اليوم / غدًا / بالتاريخ) */
    private function whenPhrase(Event $event): string
    {
        $start = $event->starts_at;

        if ($start->lte(now()->addHours(3))) {
            return 'يبدأ قريبًا';
        }

        if ($start->isToday()) {
            return 'اليوم';
        }

        if ($start->isTomorrow()) {
            return 'غدًا';
        }

        return 'في '.$start->translatedFormat('l j F');
    }

    /** بريد تذكير للعضو (إن كانت إشعاراته مفعّلة)، ولا يوقف الباقي عند فشل الإرسال */
    private function mailReminder($user, Event $event): void
    {
        if (! $user->notifications_enabled || ! $user->email) {
            return;
        }

        try {
            $body = "مرحبًا {$user->name}،\n\n"
                ."تذكير بأن لقاء «{$event->title}» يبدأ {$event->whenLabel()}.\n"
                .'تفاصيل اللقاء: '.route('events.show', $event)."\n\n"
                .'نتمنى لك لقاءً مثمرًا.'."\n"
                .config('app.name');

            Mail::raw($body, fn ($m) => $m->to($user->email)->subject('تذكير: لقاء «'.$event->title.'» '.$this->whenPhrase($event)));
        } catch (\Throwable $e) {
            Log::warning('reminder mail failed: '.$e->getMessage());
        }
    }
}
