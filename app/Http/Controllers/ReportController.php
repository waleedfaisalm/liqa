<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    /** نموذج الإبلاغ: ?type=user|event&id=... */
    public function create(Request $request): View|RedirectResponse
    {
        [$target, $error] = $this->resolve($request, (string) $request->query('type'), (int) $request->query('id'));

        if ($error) {
            return redirect()->route('landing')->with('error', $error);
        }

        return view('reports.create', [
            'type' => $request->query('type'),
            'target' => $target,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'type' => ['required', Rule::in(['user', 'event'])],
            'id' => ['required', 'integer'],
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'details' => [Rule::requiredIf($request->input('reason') === 'other'), 'nullable', 'string', 'max:500'],
        ], [
            'reason.required' => 'يرجى اختيار سبب البلاغ.',
            'details.required' => 'يرجى كتابة التفاصيل عند اختيار "سبب آخر".',
            'details.max' => 'التفاصيل طويلة جدًا (الحد الأقصى 500 حرف).',
        ]);

        [$target, $error] = $this->resolve($request, $input['type'], (int) $input['id']);

        if ($error) {
            return redirect()->route('landing')->with('error', $error);
        }

        $back = $input['type'] === 'user' ? route('users.show', $target) : route('events.show', $target);

        $exists = Report::where('reporter_id', $request->user()->id)
            ->where('target_type', $input['type'])
            ->where('target_id', $target->id)
            ->where('status', 'open')
            ->exists();

        if ($exists) {
            return redirect()->to($back)->with('error', 'سبق أن أرسلت بلاغًا عن هذا الطرف، وهو قيد المراجعة.');
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'target_type' => $input['type'],
            'target_id' => $target->id,
            'reason' => $input['reason'],
            'details' => $input['details'] ?? null,
            'status' => 'open',
        ]);

        $this->alertOwner($report, $input['type'], $target);

        return redirect()->to($back)->with('success', 'تم استلام بلاغك وسيُراجَع من إدارة المنصة. شكرًا لمساهمتك في الحفاظ على المجتمع.');
    }

    /** تنبيه بريدي للمالك عند ورود بلاغ؛ لا يوقف العملية إن فشل الإرسال */
    private function alertOwner(Report $report, string $type, $target): void
    {
        try {
            $owner = User::where('is_super_admin', true)->first();
            if (! $owner) {
                return;
            }
            $name = $type === 'user' ? $target->name : $target->title;
            \Illuminate\Support\Facades\Mail::send('emails.action', [
                'heading' => 'بلاغ جديد في لقاء',
                'lines' => [
                    'السبب: '.(Report::REASONS[$report->reason] ?? $report->reason),
                    ($type === 'user' ? 'المستخدم: ' : 'الحدث: ').$name,
                ],
                'button' => 'مراجعة البلاغ',
                'url' => route('admin.reports'),
                'footnote' => 'وصلك هذا التنبيه لأنك مالك المنصة.',
            ], fn ($m) => $m->to($owner->email)->subject('بلاغ جديد — لقاء'));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** يعيد [الهدف, رسالة خطأ] */
    private function resolve(Request $request, string $type, int $id): array
    {
        $me = $request->user();

        if ($type === 'user') {
            $user = User::find($id);

            if (! $user) {
                return [null, 'المستخدم غير موجود.'];
            }
            if ($user->is($me)) {
                return [null, 'لا يمكنك الإبلاغ عن نفسك.'];
            }

            return [$user, null];
        }

        if ($type === 'event') {
            $event = Event::with('host')->find($id);

            if (! $event) {
                return [null, 'الحدث غير موجود.'];
            }
            if ($event->isHostedBy($me)) {
                return [null, 'لا يمكنك الإبلاغ عن حدثك.'];
            }

            return [$event, null];
        }

        return [null, 'نوع البلاغ غير صحيح.'];
    }
}
