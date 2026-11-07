<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Report;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** يحذف حسابات الديمو (@liqa.test) وكل ما يتبعها قبل إطلاق المنصة */
class CleanDemoData extends Command
{
    protected $signature = 'liqa:clean-demo {--also= : بريد إضافي أو أكثر مفصولة بفاصلة لحذفها أيضًا} {--force : التنفيذ دون سؤال}';

    protected $description = 'حذف بيانات التجربة (حسابات @liqa.test وأحداثها وطلباتها وتقييماتها)';

    public function handle(): int
    {
        $extra = array_filter(array_map('trim', explode(',', (string) $this->option('also'))));

        $users = User::where('is_admin', false)
            ->where(fn ($q) => $q->where('email', 'like', '%@liqa.test')->orWhereIn('email', $extra))
            ->get();

        if ($users->isEmpty()) {
            $this->info('لا توجد بيانات تجريبية لحذفها.');

            return self::SUCCESS;
        }

        $ids = $users->pluck('id');
        $this->line('الحسابات التي ستُحذف:');
        $users->each(fn ($u) => $this->line(" - {$u->name} <{$u->email}>"));
        $this->line('عدد الأحداث التابعة لها: '.Event::whereIn('user_id', $ids)->count());

        if (! $this->option('force') && ! $this->confirm('تأكيد الحذف النهائي؟ لا يمكن التراجع.')) {
            $this->warn('أُلغيت العملية.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($users, $ids) {
            Report::where('target_type', 'user')->whereIn('target_id', $ids)->delete();
            Report::whereIn('reporter_id', $ids)->delete();
            $users->each->delete();
        });

        $this->info('تم حذف '.$users->count().' حسابًا وبياناتها.');

        return self::SUCCESS;
    }
}
