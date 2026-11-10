<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\View\View;

class LegalPageController extends Controller
{
    public const PAGES = [
        'privacy' => 'سياسة الخصوصية',
        'terms' => 'الشروط والأحكام',
    ];

    /** نص الصفحة: المخصّص إن وُجد، وإلا النص الافتراضي */
    public static function content(string $type): string
    {
        $custom = trim((string) Setting::get($type.'_content'));

        return $custom !== '' ? $custom : trim((string) file_get_contents(resource_path("legal/{$type}.txt")));
    }

    public function show(string $type): View
    {
        abort_unless(isset(self::PAGES[$type]) && Setting::enabled($type.'_enabled'), 404);

        $text = str_replace('{email}', \App\Models\Setting::get('support_email') ?: (string) config('mail.from.address'), self::content($type));

        // العنوان: سطر يبدأ بـ "# " (حتى لو لحقه النص مباشرة)، والفقرات تُفصل بسطر فارغ
        $blocks = collect();
        foreach (preg_split('/\R{2,}/', $text) as $chunk) {
            $para = [];
            foreach (preg_split('/\R/', trim($chunk)) as $line) {
                if (str_starts_with($line, '# ')) {
                    if ($para) {
                        $blocks->push(['p', trim(implode("\n", $para))]);
                        $para = [];
                    }
                    $blocks->push(['h', trim(substr($line, 2))]);
                } else {
                    $para[] = $line;
                }
            }
            if ($para && trim(implode('', $para)) !== '') {
                $blocks->push(['p', trim(implode("\n", $para))]);
            }
        }

        return view('pages.legal', [
            'title' => self::PAGES[$type],
            'blocks' => $blocks,
            'updated' => Setting::updatedAt($type.'_content'),
        ]);
    }
}
