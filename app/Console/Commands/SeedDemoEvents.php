<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * يضيف أحداثًا قادمة متنوعة لحسابات الديمو، لتجربة صفحة الأحداث والحفظ والانضمام.
 * يمكن تشغيله أكثر من مرة دون تكرار الأحداث.
 */
class SeedDemoEvents extends Command
{
    protected $signature = 'liqa:demo-events';

    protected $description = 'إضافة أحداث قادمة تجريبية متنوعة للتجربة';

    private const USERS = [
        'maha@liqa.test' => ['مها خالد', 'مصمم'],
        'karim@liqa.test' => ['كريم سامي', 'مطوّر'],
        'noor@liqa.test' => ['نور حسن', 'كاتب محتوى'],
        'youssef@liqa.test' => ['يوسف عادل', 'مسوّق'],
    ];

    public function handle(): int
    {
        $users = collect(self::USERS)->map(function ($info, $email) {
            return User::where('email', $email)->first() ?? User::unguarded(fn () => User::create([
                'name' => $info[0],
                'email' => $email,
                'password' => 'password',
                'specialty' => $info[1],
                'onboarded_at' => now(),
                'email_verified_at' => now(),
            ]));
        });

        $events = [
            ['maha@liqa.test', 'ورشة عمل: بناء هوية بصرية لعميلك الأول', 'design', 'in_person', 'الرياض', 'مساحة عمل مشتركة · حي العليا', 2, 17, 120, 12,
                'ورشة عملية نبني فيها هوية بصرية متكاملة خطوة بخطوة، ونناقش كيف تعرض عملك على العميل وتقنعه بالتسعير.'],
            ['karim@liqa.test', 'جلسة مراجعة أكواد وأفكار مشاريع', 'programming', 'online', null, 'https://meet.example.com/code-review', 3, 20, 90, 8,
                'جلسة مفتوحة لعرض مشاريعكم ومراجعة الأكواد وتبادل الحلول التقنية بين المطوّرين.'],
            ['noor@liqa.test', 'كيف تكتب عرضًا يقنع العميل؟', 'content', 'in_person', 'جدة', 'مقهى الشاطئ · حي الشاطئ', 5, 18, 120, 10,
                'نتدرّب على كتابة عروض الأسعار ورسائل التواصل مع العملاء الجدد، مع أمثلة حقيقية من تجارب الحاضرين.'],
            ['youssef@liqa.test', 'تسويق خدماتك كفريلانسر: من أين تبدأ؟', 'marketing', 'online', null, 'https://meet.example.com/marketing-start', 7, 21, 90, 15,
                'نتحدث عن بناء حضورك المهني على الإنترنت، وجذب أول العملاء دون ميزانية إعلانات كبيرة.'],
            ['maha@liqa.test', 'لقاء تعارف للفريلانسرز: اشرب قهوة وتعرّف', 'business', 'in_person', 'الدمام', 'مقهى الواجهة · الكورنيش', 9, 19, 180, 20,
                'لقاء غير رسمي للتعارف وتبادل الفرص والخبرات بين الفريلانسرز من مختلف المجالات.'],
            ['noor@liqa.test', 'جلسة مصغّرة: مراجعة ملفات الأعمال (مكتمل العدد)', 'design', 'online', null, 'https://meet.example.com/portfolio', 4, 19, 60, 2,
                'جلسة صغيرة لمراجعة ملفات الأعمال بشكل فردي. العدد مكتمل، وهي مناسبة لتجربة قائمة الانتظار.'],
            ['karim@liqa.test', 'أساسيات التعاقد وحماية حقوقك مع العملاء', 'business', 'online', null, 'https://meet.example.com/contracts', 12, 20, 120, 6,
                'نراجع معًا بنودًا أساسية في عقود العمل الحر، وكيف تحمي وقتك ومستحقاتك المالية.'],
        ];

        $created = 0;

        foreach ($events as [$hostEmail, $title, $type, $mode, $city, $location, $days, $hour, $minutes, $capacity, $description]) {
            $host = $users[$hostEmail];

            if (Event::where('user_id', $host->id)->where('title', $title)->exists()) {
                continue;
            }

            $event = new Event([
                'title' => $title,
                'type' => $type,
                'mode' => $mode,
                'country' => $city ? 'السعودية' : null,
                'city' => $city,
                'location' => $location,
                'starts_at' => now()->addDays($days)->setTime($hour, 0),
                'duration_minutes' => $minutes,
                'capacity' => $capacity,
                'description' => $description,
            ]);
            $event->user_id = $host->id;
            $event->status = Event::PUBLISHED;
            $event->save();

            // مشاركون مقبولون من الحسابات التجريبية الأخرى
            $users->except($hostEmail)->take(2)->each(fn ($u) => EventRequest::create([
                'event_id' => $event->id,
                'user_id' => $u->id,
                'status' => EventRequest::ACCEPTED,
            ]));

            $created++;
        }

        $this->info("تمت إضافة {$created} حدث قادم للتجربة.");

        return self::SUCCESS;
    }
}
