<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\Automation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** رسالة إعادة تنشيط لمن غاب عن المنصة المدة المحددة (مرة واحدة كل 60 يومًا كحد أدنى) */
class ReactivateMembers extends Command
{
    protected $signature = 'liqa:reactivate {--limit=15}';

    protected $description = 'إرسال رسالة إعادة تنشيط للأعضاء الغائبين';

    public function handle(): int
    {
        if (! Automation::on('auto_reactivation')) {
            $this->info('إعادة التنشيط معطّلة من الإعدادات.');

            return self::SUCCESS;
        }

        $days = max(7, Automation::int('auto_reactivation_days'));

        $upcoming = Event::where('status', Event::PUBLISHED)->where('starts_at', '>', now())->orderBy('starts_at')->limit(3)->get();
        if ($upcoming->isEmpty()) {
            $this->info('لا توجد أحداث قادمة؛ لا حاجة لرسالة الآن.');

            return self::SUCCESS;
        }

        $cut = now()->subDays($days);
        $oldest = now()->subDays(180);

        $users = User::where('is_admin', false)
            ->whereNull('suspended_at')
            ->whereNotNull('onboarded_at')
            ->where('notifications_enabled', true)
            ->whereRaw('coalesce(last_seen_at, created_at) < ?', [$cut])
            ->whereRaw('coalesce(last_seen_at, created_at) > ?', [$oldest])
            ->where(fn ($q) => $q->whereNull('reactivation_sent_at')->orWhere('reactivation_sent_at', '<', now()->subDays(60)))
            ->orderBy('last_seen_at')
            ->limit((int) $this->option('limit'))
            ->get();

        $sent = 0;
        foreach ($users as $user) {
            $gone = (int) ($user->last_seen_at ?? $user->created_at)->diffInDays(now());
            $lines = [
                "مرّ {$gone} يومًا منذ آخر زيارة لك، وهذه لقاءات قادمة قد تهمّك:",
                ...$upcoming->map(fn ($e) => '• '.$e->title.' — '.$e->starts_at->translatedFormat('l j F'))->all(),
            ];

            UserNotification::send($user, 'reactivation', 'اشتقنا لك! هناك '.$upcoming->count().' لقاءات قادمة بانتظارك.', route('events.index'));

            try {
                Mail::send('emails.action', [
                    'heading' => 'اشتقنا لك يا '.$user->name,
                    'lines' => $lines,
                    'button' => 'تصفّح اللقاءات',
                    'url' => route('events.index'),
                    'footnote' => 'يمكنك إيقاف هذه الرسائل من إعدادات الإشعارات في حسابك.',
                ], fn ($m) => $m->to($user->email)->subject('اشتقنا لك في لقاء'));
            } catch (\Throwable $e) {
                report($e);
            }

            $user->forceFill(['reactivation_sent_at' => now()])->save();
            $sent++;
        }

        $this->info("رسائل إعادة التنشيط: {$sent}");

        return self::SUCCESS;
    }
}
