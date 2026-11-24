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
            'reason.required' => __('يرجى اختيار سبب البلاغ.'),
            'details.required' => __('يرجى كتابة التفاصيل عند اختيار "سبب آخر".'),
            'details.max' => __('التفاصيل طويلة جدًا (الحد الأقصى 500 حرف).'),
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
            return redirect()->to($back)->with('error', __('سبق أن أرسلت بلاغًا عن هذا الطرف، وهو قيد المراجعة.'));
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
        $this->urgentAlert($input['type'], $target);

        return redirect()->to($back)->with('success', __('تم استلام بلاغك وسيُراجَع من إدارة المنصة. شكرًا لمساهمتك في الحفاظ على المجتمع.'));
    }

    /** تنبيه عاجل للمشرفين حين يبلغ عدد البلاغات المفتوحة (من مبلِّغين مختلفين) على الطرف نفسه الحد المحدد */
    private function urgentAlert(string $type, $target): void
    {
        if (! \App\Support\Automation::on('auto_report_alert')) {
            return;
        }

        try {
            $n = Report::where('target_type', $type)->where('target_id', $target->id)->where('status', 'open')->distinct('reporter_id')->count('reporter_id');

            if ($n < max(2, \App\Support\Automation::int('auto_report_threshold'))) {
                return;
            }

            $name = $type === 'user' ? $target->name : $target->title;
            $message = 'عاجل: '.$n.' بلاغات مفتوحة على '.($type === 'user' ? 'المستخدم ' : 'الحدث ').'«'.$name.'».';

            $admins = User::where('is_admin', true)->orWhere('is_super_admin', true)->get()
                ->filter(fn (User $u) => $u->isOwner() || $u->hasPermission('reports'));

            foreach ($admins as $admin) {
                \App\Models\UserNotification::send($admin, 'report_urgent', $message, route('admin.reports'));
            }

            $owner = $admins->first(fn (User $u) => $u->isOwner());
            if ($owner) {
                \Illuminate\Support\Facades\Mail::send('emails.action', [
                    'heading' => __('تنبيه عاجل: بلاغات متكررة'),
                    'lines' => [$message, __('يُنصح بمراجعتها قبل غيرها.')],
                    'button' => __('مراجعة البلاغات'),
                    'url' => route('admin.reports'),
                    'footnote' => __('وصلك هذا التنبيه لأنك مالك المنصة.'),
                ], fn ($m) => $m->to($owner->email)->subject(__('عاجل: بلاغات متكررة — لقاء')));
            }
        } catch (\Throwable $e) {
            report($e);
        }
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
                'heading' => __('بلاغ جديد في لقاء'),
                'lines' => [
                    __('السبب: ').(Report::REASONS[$report->reason] ?? $report->reason),
                    ($type === 'user' ? __('المستخدم: ') : __('الحدث: ')).$name,
                ],
                'button' => __('مراجعة البلاغ'),
                'url' => route('admin.reports'),
                'footnote' => __('وصلك هذا التنبيه لأنك مالك المنصة.'),
            ], fn ($m) => $m->to($owner->email)->subject(__('بلاغ جديد — لقاء')));
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
                return [null, __('المستخدم غير موجود.')];
            }
            if ($user->is($me)) {
                return [null, __('لا يمكنك الإبلاغ عن نفسك.')];
            }

            return [$user, null];
        }

        if ($type === 'event') {
            $event = Event::with('host')->find($id);

            if (! $event) {
                return [null, __('الحدث غير موجود.')];
            }
            if ($event->isHostedBy($me)) {
                return [null, __('لا يمكنك الإبلاغ عن حدثك.')];
            }

            return [$event, null];
        }

        return [null, __('نوع البلاغ غير صحيح.')];
    }
}
