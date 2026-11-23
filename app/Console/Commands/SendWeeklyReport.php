<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AnalyticsReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** يرسل تقرير الأداء الأسبوعي إلى المالك (أو إلى بريد محدد بخيار --to) */
class SendWeeklyReport extends Command
{
    protected $signature = 'liqa:weekly-report {--days=7} {--to=}';

    protected $description = 'إرسال تقرير أسبوعي بمؤشرات المنصة إلى المالك';

    public function handle(): int
    {
        $report = AnalyticsReport::build(max(1, (int) $this->option('days')));
        $html = view('emails.weekly-report', ['r' => $report])->render();

        $to = $this->option('to')
            ? [$this->option('to')]
            : User::where('is_super_admin', true)->whereNotNull('email')->pluck('email')->all();

        if (! $to) {
            $this->warn('لا يوجد مستلم للتقرير.');

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($to as $address) {
            try {
                Mail::html($html, fn ($m) => $m->to($address)->subject('تقرير لقاء الأسبوعي · '.now()->format('Y-m-d')));
                $sent++;
            } catch (\Throwable $e) {
                $this->warn('تعذّر الإرسال إلى '.$address.': '.$e->getMessage());
            }
        }

        $this->info("أُرسل التقرير إلى {$sent} مستلم.");

        return self::SUCCESS;
    }
}
