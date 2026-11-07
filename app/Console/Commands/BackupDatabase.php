<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/** نسخة احتياطية من قاعدة بيانات SQLite، مع الاحتفاظ بآخر N نسخة */
class BackupDatabase extends Command
{
    protected $signature = 'liqa:backup {--keep=14 : عدد النسخ المحفوظة}';

    protected $description = 'إنشاء نسخة احتياطية من قاعدة البيانات';

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->error('هذا الأمر يدعم SQLite فقط. استخدم أداة النسخ الاحتياطي لقاعدة بياناتك الحالية.');

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $file = $dir.'/liqa-'.now()->format('Y-m-d_His').'.sqlite';
        DB::statement('VACUUM INTO '.DB::getPdo()->quote($file));

        $old = collect(File::files($dir))->sortByDesc(fn ($f) => $f->getMTime())->slice(max(1, (int) $this->option('keep')));
        $old->each(fn ($f) => File::delete($f->getPathname()));

        $this->info('تم إنشاء النسخة: '.$file);

        return self::SUCCESS;
    }
}
