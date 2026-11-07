<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * يضيف حدثًا منتهيًا لمستخدم محدد للتجربة:
 * المستخدم هو المنظّم، والمشاركون المقبولون هم حسابات الديمو.
 * المشاركون قيّموا المنظّم مسبقًا، أما تقييم المنظّم لهم فيبقى لك.
 */
class SeedPastEvent extends Command
{
    protected $signature = 'liqa:past-event {email : بريد المستخدم المنظّم}';

    protected $description = 'إضافة حدث منتهي لمستخدم مع مشاركين تجريبيين للتجربة';

    private const PEOPLE = [
        ['maha@liqa.test', 'مها خالد', 'مصمم', 5, ['ملتزم بالموعد', 'محترم'], 'تنظيم ممتاز ونقاش مفيد، شكرًا على الدعوة.'],
        ['karim@liqa.test', 'كريم سامي', 'مطوّر', 4, ['حديثه مفيد'], 'لقاء جيد وفرصة طيبة للتعارف.'],
        ['noor@liqa.test', 'نور حسن', 'كاتب محتوى', 5, ['أتاح لي فرصًا', 'محترم'], null],
        ['youssef@liqa.test', 'يوسف عادل', 'مسوّق', 4, ['ملتزم بالموعد'], 'إدارة منظّمة للوقت.'],
    ];

    public function handle(): int
    {
        $host = User::where('email', $this->argument('email'))->first();

        if (! $host) {
            $this->error('لا يوجد مستخدم بهذا البريد. سجّل الحساب أولًا ثم أعد المحاولة.');

            return self::FAILURE;
        }

        $title = 'لقاء الفريلانسرز السابق';

        if (Event::where('user_id', $host->id)->where('title', $title)->exists()) {
            $this->warn('الحدث موجود مسبقًا لهذا المستخدم، لم يتم تغيير شيء.');

            return self::SUCCESS;
        }

        $event = new Event([
            'title' => $title,
            'type' => 'business',
            'mode' => 'in_person',
            'city' => 'الرياض',
            'location' => 'مقهى X · حي العليا',
            'starts_at' => now()->subDays(3)->setTime(18, 0),
            'duration_minutes' => 120,
            'capacity' => 8,
            'description' => 'لقاء للتعارف وتبادل الخبرات والفرص بين الفريلانسرز. حدث تجريبي منتهٍ لاختبار التقييم المتبادل.',
        ]);
        $event->user_id = $host->id;
        $event->status = Event::PUBLISHED;
        $event->save();

        foreach (self::PEOPLE as [$email, $name, $specialty, $stars, $tags, $comment]) {
            $person = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => 'password',
                    'specialty' => $specialty,
                    'onboarded_at' => now(),
                ],
            );

            EventRequest::create(['event_id' => $event->id, 'user_id' => $person->id, 'status' => EventRequest::ACCEPTED]);

            // المشارك يقيّم المنظّم (أنت)
            Rating::create([
                'event_id' => $event->id,
                'rater_id' => $person->id,
                'ratee_id' => $host->id,
                'stars' => $stars,
                'tags' => $tags,
                'comment' => $comment,
            ]);
        }

        $this->info('تمت إضافة الحدث «'.$title.'» للمستخدم '.$host->email.' بـ '.count(self::PEOPLE).' مشاركين.');
        $this->line('افتح "أحداثي" ثم الحدث، وقيّم المشاركين من قسم "قيّم المشاركين".');

        return self::SUCCESS;
    }
}
