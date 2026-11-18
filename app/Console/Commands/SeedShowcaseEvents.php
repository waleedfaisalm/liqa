<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * يضيف أحداثًا واقعية قادمة باسم حساب المالك (غرف لقاء المباشرة، بلا روابط وهمية).
 * آمن للتكرار: لا يكرّر حدثًا موجودًا بنفس العنوان. فعّله بالمتغير LIQA_SHOWCASE=1 ثم احذفه.
 */
class SeedShowcaseEvents extends Command
{
    protected $signature = 'liqa:showcase-events';

    protected $description = 'إضافة أحداث قادمة واقعية باسم حساب المالك';

    private const ROOM = 'غرفة لقاء المباشرة';

    public function handle(): int
    {
        $email = strtolower(trim((string) env('OWNER_EMAIL')));
        $host = ($email !== '' ? User::where('email', $email)->first() : null)
            ?? User::where('is_admin', true)->orderBy('id')->first();

        if (! $host) {
            $this->warn('لا يوجد حساب مالك لإضافة الأحداث باسمه.');

            return self::SUCCESS;
        }

        // العنوان، النوع، بعد كم يومًا، الساعة، الدقائق، المقاعد، الوصف
        $events = [
            ['لقاء تعارف للمستقلين: من أنت وماذا تقدّم؟', 'business', 2, 20, 60, 20,
                'لقاء مفتوح نتعرّف فيه على بعضنا: كل مشارك يعرّف بنفسه وخدماته في دقيقتين، ونتبادل الفرص والأفكار بعيدًا عن الرسميات.'],
            ['ورشة: كيف تعرض أعمالك على العميل وتقنعه بالسعر؟', 'design', 4, 20, 90, 15,
                'ورشة عملية حول ترتيب ملف الأعمال، وطريقة عرض المشروع على العميل، والرد على اعتراض السعر بثقة، مع أمثلة من تجارب الحاضرين.'],
            ['جلسة مراجعة مشاريع المطوّرين المستقلين', 'programming', 6, 21, 120, 10,
                'يعرض كل مطوّر مشروعًا أو جزءًا من كود يعمل عليه، ونراجعه معًا: البنية والأداء والأمان وطرق التسليم للعميل.'],
            ['كيف تجد أول ثلاثة عملاء كمستقل؟', 'marketing', 8, 20, 90, 20,
                'نناقش خطوات عملية للحصول على أول العملاء: تحديد خدمتك، وبناء حضورك المهني، والتواصل مع العملاء المحتملين دون ميزانية إعلانات.'],
            ['جلسة كتابة مشتركة: ساعتان من التركيز', 'content', 10, 19, 120, 12,
                'نجتمع للعمل بصمت وتركيز على مشاريعنا، مع فواصل قصيرة للتعارف وتبادل الملاحظات. مناسبة لكتّاب المحتوى والمترجمين والمصممين.'],
        ];

        $created = 0;

        foreach ($events as [$title, $type, $days, $hour, $minutes, $capacity, $description]) {
            if (Event::where('user_id', $host->id)->where('title', $title)->exists()) {
                continue;
            }

            $event = new Event([
                'title' => $title,
                'type' => $type,
                'mode' => 'online',
                'location' => self::ROOM,
                'starts_at' => now()->addDays($days)->setTime($hour, 0),
                'duration_minutes' => $minutes,
                'capacity' => $capacity,
                'description' => $description,
            ]);
            $event->user_id = $host->id;
            $event->status = Event::PUBLISHED;
            $event->save();
            $created++;
        }

        $this->info("تمت إضافة {$created} حدث واقعي باسم {$host->name}.");

        return self::SUCCESS;
    }
}
