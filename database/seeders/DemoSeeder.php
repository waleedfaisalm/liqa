<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventComment;
use App\Models\EventRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * بيانات تجريبية للتطوير المحلي بس.
 * كل الحسابات كلمة السر بتاعتها: password
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = collect([
            ['مها خالد', 'maha@liqa.test', 'مصمم', 'مصممة فريلانس، أحب تبادل الخبرات مع متخصصين من مجالي.'],
            ['كريم سامي', 'karim@liqa.test', 'مطوّر', 'أعمل على مشاريع ويب لعملاء من الخليج.'],
            ['نور حسن', 'noor@liqa.test', 'كاتب محتوى', 'كاتبة محتوى، أبحث عن شراكات مهنية.'],
            ['يوسف عادل', 'youssef@liqa.test', 'مسوّق', 'مسوّق رقمي مهتم بالتواصل المهني.'],
        ])->mapWithKeys(function ($row) {
            [$name, $email, $specialty, $bio] = $row;

            $user = User::unguarded(fn () => User::create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'specialty' => $specialty,
                'bio' => $bio,
                'onboarded_at' => now(),
                'email_verified_at' => now(),
            ]));

            return [$email => $user];
        });

        $maha = $users['maha@liqa.test'];
        $karim = $users['karim@liqa.test'];
        $noor = $users['noor@liqa.test'];
        $youssef = $users['youssef@liqa.test'];

        // حدث جاي مفتوح
        $weekly = $this->event($maha, [
            'title' => 'لقاء الفريلانسرز الأسبوعي',
            'type' => 'business', 'mode' => 'in_person',
            'city' => 'الرياض', 'location' => 'مقهى X · حي العليا',
            'starts_at' => now()->addDays(2)->setTime(18, 0),
            'capacity' => 8,
            'description' => 'لقاء للتعارف وتبادل الخبرات والفرص بين الفريلانسرز. يمكنك الحضور بأعمالك وأفكارك، دون أي التزامات.',
        ]);

        $this->join($weekly, $karim, EventRequest::ACCEPTED);
        $this->join($weekly, $noor, EventRequest::PENDING);

        EventComment::create(['event_id' => $weekly->id, 'user_id' => $karim->id, 'body' => 'هل المكان قريب من وسائل المواصلات؟']);
        EventComment::create(['event_id' => $weekly->id, 'user_id' => $maha->id, 'body' => 'نعم، على بُعد خمس دقائق سيرًا من المحطة.']);

        // حدث أونلاين
        $this->event($karim, [
            'title' => 'جلسة تبادل خبرات للمطورين',
            'type' => 'programming', 'mode' => 'online',
            'location' => 'https://meet.example.com/liqa-devs',
            'starts_at' => now()->addDays(4)->setTime(16, 0),
            'capacity' => 10,
            'description' => 'نناقش العمل الحر مع عملاء من الخارج، وكيفية التسعير والتفاوض.',
        ]);

        // حدث للنهاردة
        $this->event($noor, [
            'title' => 'جلسة عمل مشترك: ساعتان من التركيز',
            'type' => 'content', 'mode' => 'in_person',
            'city' => 'الرياض', 'location' => 'مساحة عمل Y · حي الملقا',
            'starts_at' => now()->addHours(5),
            'capacity' => 6,
            'description' => 'ساعتان من العمل المشترك بتركيز عالٍ ودون مشتتات.',
        ]);

        // حدث سابق (عشان تجرّب التقييم)
        $past = $this->event($youssef, [
            'title' => 'لقاء المسوّقين الحر',
            'type' => 'marketing', 'mode' => 'in_person',
            'city' => 'الرياض', 'location' => 'مقهى Z · حي السليمانية',
            'starts_at' => now()->subDays(2)->setTime(17, 0),
            'capacity' => 6,
            'description' => 'لقاء مفتوح بين المسوّقين لتبادل الخبرات.',
        ]);

        $this->join($past, $maha, EventRequest::ACCEPTED);
        $this->join($past, $karim, EventRequest::ACCEPTED);
    }

    private function event(User $host, array $data): Event
    {
        $event = new Event($data);
        $event->user_id = $host->id;
        $event->duration_minutes = 120;
        $event->status = Event::PUBLISHED;
        $event->save();

        return $event;
    }

    private function join(Event $event, User $user, string $status): void
    {
        EventRequest::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => $status]);
    }
}
