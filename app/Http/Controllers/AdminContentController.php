<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** إدارة محتوى المنصة: الهوية، نصوص الرئيسية، المجالات والتخصصات، الدول والمدن (للمالك) */
class AdminContentController extends Controller
{
    // ---------- الهوية ----------

    public function identity(): View
    {
        return view('admin.content.identity', [
            'v' => [
                'site_name' => Setting::site('name'),
                'site_tagline' => Setting::site('tagline'),
                'support_email' => (string) Setting::get('support_email'),
                'social_x' => (string) Setting::get('social_x'),
                'social_instagram' => (string) Setting::get('social_instagram'),
                'social_linkedin' => (string) Setting::get('social_linkedin'),
            ],
        ]);
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $url = ['nullable', 'url:https', 'max:200'];
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:30'],
            'site_tagline' => ['required', 'string', 'max:120'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'social_x' => $url,
            'social_instagram' => $url,
            'social_linkedin' => $url,
        ], [
            'site_name.required' => 'اسم المنصة مطلوب.',
            'site_tagline.required' => 'الشعار الفرعي مطلوب.',
            'support_email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            '*.url' => 'يجب أن يبدأ الرابط بـ https://',
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, trim((string) $value));
        }

        AdminLog::record($request->user(), 'settings.updated', null, null, 'هوية المنصة', 'تحديث بيانات الهوية');

        return back()->with('success', 'تم حفظ هوية المنصة.');
    }

    // ---------- نصوص الصفحة الرئيسية ----------

    public function landing(): View
    {
        return view('admin.content.landing', ['L' => Catalog::landing(), 'custom' => Setting::json('landing') !== null]);
    }

    public function updateLanding(Request $request): RedirectResponse
    {
        if ($request->input('action') === 'reset') {
            Setting::putJson('landing', null);
            AdminLog::record($request->user(), 'settings.updated', null, null, 'نصوص الصفحة الرئيسية', 'استعادة الافتراضي');

            return back()->with('success', 'تمت استعادة نصوص الصفحة الرئيسية الافتراضية.');
        }

        $defaults = config('landing');
        $input = (array) $request->input('landing', []);
        $clean = [];

        foreach ($defaults as $section => $fields) {
            foreach ($fields as $key => $default) {
                if (is_array($default)) {
                    foreach ($default as $i => $card) {
                        foreach ($card as $ck => $_) {
                            $clean[$section][$key][$i][$ck] = $this->text($input[$section][$key][$i][$ck] ?? '');
                        }
                    }
                } else {
                    $clean[$section][$key] = $this->text($input[$section][$key] ?? '');
                }
            }
        }

        Setting::putJson('landing', $clean);
        AdminLog::record($request->user(), 'settings.updated', null, null, 'نصوص الصفحة الرئيسية', 'تحديث النصوص');

        return back()->with('success', 'تم حفظ نصوص الصفحة الرئيسية.');
    }

    private function text(mixed $v): string
    {
        $v = trim(strip_tags((string) $v));

        if (mb_strlen($v) > 300) {
            throw ValidationException::withMessages(['landing' => 'أحد النصوص طويل جدًا (الحد الأقصى 300 حرف).']);
        }

        return $v;
    }

    // ---------- المجالات والتخصصات ----------

    public function categories(): View
    {
        return view('admin.content.categories', [
            'types' => Catalog::eventTypeRows(),
            'specialties' => Catalog::specialtyRows(),
        ]);
    }

    public function updateCategories(Request $request): RedirectResponse
    {
        $request->validate([
            'types' => ['required', 'array', 'min:1', 'max:30'],
            'types.*.label' => ['required', 'string', 'max:40'],
            'specialties' => ['required', 'array', 'min:1', 'max:30'],
            'specialties.*.label' => ['required', 'string', 'max:40'],
        ], [
            'types.*.label.required' => 'اسم المجال لا يمكن أن يكون فارغًا.',
            'specialties.*.label.required' => 'اسم التخصص لا يمكن أن يكون فارغًا.',
            '*.max' => 'الاسم طويل (الحد الأقصى 40 حرفًا).',
        ]);

        // مجالات الأحداث
        $existing = collect(Catalog::eventTypeRows())->pluck('key')->all();
        $types = [];
        foreach ((array) $request->input('types') as $row) {
            $key = (string) ($row['key'] ?? '');
            $key = in_array($key, $existing, true) ? $key : 'c'.Str::lower(Str::random(7));
            $types[] = ['key' => $key, 'label' => trim($row['label']), 'active' => ! empty($row['active'])];
        }
        $this->ensureUnique(array_column($types, 'label'), 'types', 'أسماء المجالات يجب ألا تتكرر.');
        if (! collect($types)->contains('active', true)) {
            throw ValidationException::withMessages(['types' => 'يجب أن يبقى مجال واحد على الأقل ظاهرًا.']);
        }
        Setting::putJson('event_types', $types);

        // التخصصات (مع نقل التسمية الجديدة إلى المستخدمين عند إعادة التسمية)
        $specialties = [];
        $renames = [];
        foreach ((array) $request->input('specialties') as $row) {
            $label = trim($row['label']);
            $orig = trim((string) ($row['orig'] ?? ''));
            if ($orig !== '' && $orig !== $label) {
                $renames[$orig] = $label;
            }
            $specialties[] = ['label' => $label, 'active' => ! empty($row['active'])];
        }
        $this->ensureUnique(array_column($specialties, 'label'), 'specialties', 'أسماء التخصصات يجب ألا تتكرر.');
        if (! collect($specialties)->contains('active', true)) {
            throw ValidationException::withMessages(['specialties' => 'يجب أن يبقى تخصص واحد على الأقل ظاهرًا.']);
        }
        foreach ($renames as $from => $to) {
            User::where('specialty', $from)->update(['specialty' => $to]);
        }
        Setting::putJson('specialties', $specialties);

        AdminLog::record($request->user(), 'settings.updated', null, null, 'المجالات والتخصصات', 'تحديث القوائم');

        return back()->with('success', 'تم حفظ المجالات والتخصصات.');
    }

    private function ensureUnique(array $labels, string $field, string $message): void
    {
        if (count($labels) !== count(array_unique($labels))) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    // ---------- الدول والمدن ----------

    public function places(): View
    {
        return view('admin.content.places', ['places' => Catalog::places()]);
    }

    public function updatePlaces(Request $request): RedirectResponse
    {
        $request->validate([
            'places' => ['required', 'array', 'min:1', 'max:60'],
            'places.*.country' => ['required', 'string', 'max:40'],
            'places.*.cities' => ['nullable', 'string', 'max:6000'],
        ], [
            'places.*.country.required' => 'اسم الدولة لا يمكن أن يكون فارغًا.',
            'places.*.country.max' => 'اسم الدولة طويل (الحد الأقصى 40 حرفًا).',
        ]);

        $out = [];
        foreach ((array) $request->input('places') as $row) {
            $country = trim($row['country']);
            if (isset($out[$country])) {
                throw ValidationException::withMessages(['places' => 'أسماء الدول يجب ألا تتكرر.']);
            }
            $cities = collect(preg_split('/\R/u', (string) ($row['cities'] ?? '')))
                ->map(fn ($c) => trim($c))->filter()->unique()->values();
            if ($cities->contains(fn ($c) => mb_strlen($c) > 60)) {
                throw ValidationException::withMessages(['places' => 'اسم مدينة طويل جدًا (الحد الأقصى 60 حرفًا).']);
            }
            $out[$country] = $cities->all();
        }

        Setting::putJson('places', $out);
        AdminLog::record($request->user(), 'settings.updated', null, null, 'الدول والمدن', 'تحديث القائمة ('.count($out).' دولة)');

        return back()->with('success', 'تم حفظ الدول والمدن.');
    }
}
