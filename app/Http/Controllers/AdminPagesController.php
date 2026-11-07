<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPagesController extends Controller
{
    public function edit(): View
    {
        $pages = [];
        foreach (LegalPageController::PAGES as $key => $label) {
            $pages[$key] = [
                'label' => $label,
                'enabled' => Setting::enabled($key.'_enabled'),
                'content' => LegalPageController::content($key),
                'custom' => trim((string) Setting::get($key.'_content')) !== '',
            ];
        }

        return view('admin.pages', compact('pages'));
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        abort_unless(isset(LegalPageController::PAGES[$type]), 404);

        $data = $request->validate([
            'content' => ['nullable', 'string', 'max:20000'],
        ], ['content.max' => 'النص طويل جدًا.']);

        $content = trim(str_replace("\r\n", "\n", (string) ($data['content'] ?? '')));
        $default = trim((string) file_get_contents(resource_path("legal/{$type}.txt")));
        $enabled = $request->boolean('enabled');

        Setting::put($type.'_enabled', $enabled ? '1' : '0');
        if ($request->input('action') === 'reset') {
            Setting::put($type.'_content', '');
            $note = 'استعادة النص الافتراضي';
        } else {
            Setting::put($type.'_content', $content === $default ? '' : $content);
            $note = 'تحديث النص';
        }

        AdminLog::record($request->user(), 'settings.updated', null, null, LegalPageController::PAGES[$type], ($enabled ? 'الصفحة ظاهرة' : 'الصفحة مخفية').' — '.$note);

        return back()->with('success', 'تم حفظ «'.LegalPageController::PAGES[$type].'».');
    }
}
