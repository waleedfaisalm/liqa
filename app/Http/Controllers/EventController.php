<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventController extends Controller
{
    /** لوحة التحكم */
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $upcoming = Event::upcoming()
            ->with(['host', 'requests' => fn ($q) => $q->where('user_id', $user->id)])
            ->withCount('acceptedRequests')
            ->where('user_id', '!=', $user->id)
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        $myHosted = $user->hostedEvents()->upcoming()->count();
        $myJoined = $user->eventRequests()->where('status', EventRequest::ACCEPTED)
            ->whereHas('event', fn ($q) => $q->upcoming())->count();
        $pending = EventRequest::where('status', EventRequest::PENDING)
            ->whereHas('event', fn ($q) => $q->upcoming()->where('user_id', $user->id))->count();

        $hasBio = filled($user->bio) && filled($user->avatar_path);
        $steps = [
            ['done' => $hasBio, 'title' => __('أكمل ملفك الشخصي'), 'text' => __('أضف صورتك ونبذة مختصرة ليتعرّف عليك المنظّمون.'), 'url' => route('settings'), 'cta' => __('إكمال الملف')],
            ['done' => $user->hasVerifiedEmail(), 'title' => __('وثّق بريدك الإلكتروني'), 'text' => __('يضمن وصول إشعارات الطلبات والتذكيرات إليك.'), 'url' => route('verification.notice'), 'cta' => __('توثيق البريد')],
            ['done' => $user->savedEvents()->exists(), 'title' => __('احفظ لقاءً يهمّك'), 'text' => __('تصفّح اللقاءات واحفظ ما يناسب تخصصك للرجوع إليه.'), 'url' => route('events.index'), 'cta' => __('تصفّح اللقاءات')],
            ['done' => $user->eventRequests()->exists(), 'title' => __('قدّم طلب انضمام'), 'text' => __('اختر لقاءً قريبًا منك وأرسل طلبك إلى المنظّم.'), 'url' => route('events.index'), 'cta' => __('اختر لقاءً')],
            ['done' => $user->hostedEvents()->exists(), 'title' => __('انشر لقاءك الأول'), 'text' => __('حدّد الموعد والمكان وعدد المقاعد، ودع المجتمع يأتي إليك.'), 'url' => route('events.create'), 'cta' => __('انشر لقاءً')],
        ];

        return view('dashboard', compact('upcoming', 'myHosted', 'myJoined', 'pending', 'steps'));
    }

    /** قائمة الأحداث الجاية (مع فلترة) */
    public function index(Request $request): View
    {
        $user = $request->user();

        $type = $request->query('type');
        $mode = $request->query('mode');
        $country = $request->query('country');
        $search = trim((string) $request->query('q', ''));

        $type = array_key_exists((string) $type, \App\Support\Catalog::eventTypes(false)) ? $type : null;
        $mode = array_key_exists((string) $mode, Event::MODES) ? $mode : null;
        $country = array_key_exists((string) $country, \App\Support\Catalog::places()) ? $country : null;

        $events = Event::upcoming()
            ->with('host')
            ->when($user, fn ($q) => $q->with(['requests' => fn ($r) => $r->where('user_id', $user->id)]))
            ->withCount('acceptedRequests')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($mode, fn ($q) => $q->where('mode', $mode))
            ->when($country, fn ($q) => $q->where('country', $country))
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('title', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%');
            }))
            ->orderBy('starts_at')
            ->paginate(9)
            ->withQueryString();

        return view('events.index', compact('events', 'type', 'mode', 'search', 'country'));
    }

    /** أحداثي: اللي نظّمتها واللي طلبت أنضم لها */
    public function mine(Request $request): View
    {
        $user = $request->user();

        $hosted = $user->hostedEvents()
            ->withCount([
                'acceptedRequests',
                'requests as pending_requests_count' => fn ($q) => $q->where('status', EventRequest::PENDING),
            ])
            ->orderByDesc('starts_at')
            ->get();

        $joined = $user->eventRequests()
            ->with(['event' => fn ($q) => $q->withCount('acceptedRequests'), 'event.host'])
            ->latest()
            ->get()
            ->filter(fn ($r) => $r->event !== null);

        $tab = $request->query('tab') === 'joined' ? 'joined' : 'hosted';

        return view('events.mine', compact('hosted', 'joined', 'tab'));
    }

    public function create(): View|RedirectResponse
    {
        if (! \App\Models\Setting::enabled('event_creation_enabled')) {
            return redirect()->route('events.index')->with('error', __('إنشاء الأحداث الجديدة متوقف مؤقتًا من إدارة المنصة.'));
        }

        return view('events.form', ['event' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! \App\Models\Setting::enabled('event_creation_enabled')) {
            return redirect()->route('events.index')->with('error', __('إنشاء الأحداث الجديدة متوقف مؤقتًا من إدارة المنصة.'));
        }

        $validated = $request->validate($this->rules() + [
            'repeat' => ['nullable', Rule::in(['none', 'weekly', 'biweekly', 'monthly'])],
            'occurrences' => ['nullable', 'required_unless:repeat,none', 'integer', 'min:2', 'max:12'],
        ]);

        $repeat = $validated['repeat'] ?? 'none';
        $occurrences = $repeat === 'none' ? 1 : (int) ($validated['occurrences'] ?? 2);
        unset($validated['repeat'], $validated['occurrences']);

        $data = $this->normalizePlace($validated);

        // حماية من النقر المزدوج: حدث مطابق (نفس العنوان والموعد) أُنشئ للتو من نفس العضو
        $duplicate = Event::where('user_id', $request->user()->id)
            ->where('title', $data['title'])
            ->where('starts_at', $data['starts_at'])
            ->where('created_at', '>=', now()->subMinutes(2))
            ->first();

        if ($duplicate) {
            return redirect()->route('events.show', $duplicate)->with('success', __('تم نشر الحدث. يمكن للأعضاء الآن تقديم طلبات الانضمام.'));
        }

        $seriesId = $occurrences > 1 ? (string) \Illuminate\Support\Str::uuid() : null;
        $first = null;
        $start = \Illuminate\Support\Carbon::parse($data['starts_at']);

        for ($i = 0; $i < $occurrences; $i++) {
            $event = new Event($data);
            $event->starts_at = match ($repeat) {
                'weekly' => $start->copy()->addWeeks($i),
                'biweekly' => $start->copy()->addWeeks(2 * $i),
                'monthly' => $start->copy()->addMonthsNoOverflow($i),
                default => $start->copy(),
            };
            $event->user_id = $request->user()->id;
            $event->status = Event::PUBLISHED;
            $event->series_id = $seriesId;
            $event->save();
            $first ??= $event;
        }

        $message = $occurrences > 1
            ? __('تم نشر :v1 لقاءات متكررة. يمكن للأعضاء الانضمام إلى كل لقاء على حدة.', ['v1' => $occurrences])
            : __('تم نشر الحدث. يمكن للأعضاء الآن تقديم طلبات الانضمام.');

        return redirect()->route('events.show', $first)->with('success', $message);
    }

    public function show(Request $request, Event $event): View
    {
        $user = $request->user();
        $isHost = $event->isHostedBy($user);

        $event->load('host');
        $event->loadCount('acceptedRequests');

        $accepted = $event->acceptedRequests()->with('user')->get();

        $requests = $isHost
            ? $event->requests()->with('user')->orderByRaw("case status when 'pending' then 0 when 'accepted' then 1 else 2 end")->latest()->get()
            : collect();

        // قسم التقييم: المشاركون الآخرون (المنظّم + المقبولون) بعد انتهاء الحدث
        $ratable = collect();
        $myRatings = collect();
        if ($user && ! $event->isCancelled() && $event->hasEnded() && $event->isParticipant($user)) {
            $ratable = $accepted->pluck('user')->prepend($event->host)->unique('id')->reject(fn ($p) => $p->id === $user->id)->values();
            $myRatings = $event->ratings()->where('rater_id', $user->id)->get()->keyBy('ratee_id');
        }

        $eventRatings = (! $event->isCancelled() && $event->hasEnded())
            ? $event->ratings()->with(['rater', 'ratee'])->latest()->get()
            : collect();

        $myRequest = ($isHost || ! $user) ? null : $event->requestOf($user);
        $waitlistPosition = ($myRequest && $myRequest->status === EventRequest::WAITLISTED)
            ? $event->requests()->where('status', EventRequest::WAITLISTED)->where('id', '<=', $myRequest->id)->count()
            : null;

        $series = $event->series_id
            ? Event::where('series_id', $event->series_id)->where('id', '!=', $event->id)
                ->where('status', Event::PUBLISHED)->orderBy('starts_at')->limit(12)->get()
            : collect();

        // الحضور الفعلي بعد انتهاء الحدث (يراه المنظّم والمشاركون)
        $attendees = collect();
        $myAttendance = null;
        $noShows = 0;
        if (! $event->isCancelled() && $event->hasEnded() && ($isHost || ($user && $event->isParticipant($user)))) {
            $attendees = $event->attendances()->with('user')->where('seconds', '>=', \App\Models\EventAttendance::THRESHOLD)->orderByDesc('seconds')->get()
                ->filter(fn ($a) => $a->user && $a->user_id !== $event->user_id)->values();
            $noShows = max(0, $accepted->count() - $attendees->whereIn('user_id', $accepted->pluck('user_id'))->count());
            if ($user && ! $isHost) {
                $mine = $attendees->firstWhere('user_id', $user->id);
                $myAttendance = $mine;
            }
        }

        return view('events.show', [
            'attendees' => $attendees,
            'myAttendance' => $myAttendance,
            'noShows' => $noShows,
            'series' => $series,
            'waitlistPosition' => $waitlistPosition,
            'comments' => $event->comments()->with('user')->oldest()->get(),
            'eventRatings' => $eventRatings,
            'ratable' => $ratable,
            'myRatings' => $myRatings,
            'event' => $event,
            'isHost' => $isHost,
            'myRequest' => $myRequest,
            'accepted' => $accepted,
            'requests' => $requests,
        ]);
    }

    public function edit(Request $request, Event $event): View|RedirectResponse
    {
        $this->authorizeHost($request, $event);

        if ($block = $this->lockedReason($event)) {
            return redirect()->route('events.show', $event)->with('error', $block);
        }

        return view('events.form', ['event' => $event->loadCount('acceptedRequests')]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeHost($request, $event);

        if ($block = $this->lockedReason($event)) {
            return redirect()->route('events.show', $event)->with('error', $block);
        }

        $data = $this->normalizePlace($request->validate($this->rules($event->acceptedRequests()->count())));

        $event->update($data);
        $event->promoteWaitlist(); // قد تتوفر مقاعد بعد زيادة السعة

        // إخطار المقبولين بالتعديل
        $event->acceptedRequests()->with('user')->get()->each(fn ($r) => UserNotification::send(
            $r->user,
            'event_updated',
            'تم تعديل بيانات حدث «'.$event->title.'»، يرجى مراجعة الموعد والمكان.',
            route('events.show', $event),
        ));

        return redirect()->route('events.show', $event)->with('success', __('تم حفظ التعديلات.'));
    }

    /**
     * حذف الحدث:
     * - لو ما حدش طلب الانضمام: بيتحذف نهائيًا.
     * - لو في طلبات: بيتلغي (بيفضل ظاهر في أحداثي كملغي) والناس بتتخطر.
     */
    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeHost($request, $event);

        if ($event->isCancelled()) {
            return redirect()->route('events.mine')->with('error', __('هذا الحدث ملغى بالفعل.'));
        }

        if ($event->starts_at->isPast()) {
            return redirect()->route('events.show', $event)->with('error', __('لا يمكن حذف حدث بدأ أو انتهى، حفاظًا على التقييمات.'));
        }

        $affected = $event->requests()->whereIn('status', [EventRequest::PENDING, EventRequest::ACCEPTED, EventRequest::WAITLISTED])->with('user')->get();

        if ($event->requests()->doesntExist()) {
            $event->delete();

            return redirect()->route('events.mine')->with('success', __('تم حذف الحدث.'));
        }

        $event->status = Event::CANCELLED;
        $event->save();

        $affected->each(fn ($r) => UserNotification::send(
            $r->user,
            'event_cancelled',
            'تم إلغاء حدث «'.$event->title.'» من قِبل المنظّم.',
            route('events.show', $event),
        ));

        return redirect()->route('events.mine')->with('success', __('تم إلغاء الحدث وإشعار مقدّمي طلبات الانضمام.'));
    }

    // ---------------------------------------------------------

    private function rules(int $minCapacity = 2): array
    {
        return [
            'title' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(array_keys(\App\Support\Catalog::eventTypes(false)))],
            'mode' => ['required', Rule::in(array_keys(Event::MODES))],
            'country' => ['nullable', 'required_if:mode,in_person', Rule::in(array_keys(\App\Support\Catalog::places()))],
            'city' => ['nullable', 'required_if:mode,in_person', 'string', 'max:60'],
            'city_other' => ['nullable', 'required_if:city,__other', 'string', 'max:60'],
            'location' => ['required', 'string', 'max:150'],
            'starts_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', Rule::in(array_keys(Event::DURATIONS))],
            'capacity' => ['required', 'integer', 'min:'.max(2, $minCapacity), 'max:100'],
            'description' => ['required', 'string', 'min:10', 'max:600'],
            'speaker_name' => ['nullable', 'string', 'max:120'],
            'speaker_bio' => ['nullable', 'string', 'max:300'],
            'agenda' => ['nullable', 'array', 'max:8'],
            'agenda.*.title' => ['nullable', 'string', 'max:80'],
            'agenda.*.minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
        ];
    }

    /** يضبط الدولة والمدينة: لا مكان للأحداث عن بُعد، والمدينة إما من القائمة أو مكتوبة يدويًا */
    private function normalizePlace(array $data): array
    {
        if (($data['mode'] ?? null) === 'online') {
            $data['country'] = null;
            $data['city'] = null;
        } elseif (($data['city'] ?? null) === '__other') {
            $data['city'] = trim((string) ($data['city_other'] ?? ''));
        } elseif (! in_array($data['city'], (\App\Support\Catalog::places()[$data['country']] ?? []), true)) {
            throw ValidationException::withMessages(['city' => __('يرجى اختيار مدينة من القائمة.')]);
        }

        unset($data['city_other']);

        // الجدول الزمني: تجاهل الصفوف الفارغة، ولا يتجاوز مجموعه مدة الحدث
        $agenda = collect($data['agenda'] ?? [])
            ->filter(fn ($r) => trim((string) ($r['title'] ?? '')) !== '' && (int) ($r['minutes'] ?? 0) >= 5)
            ->map(fn ($r) => ['title' => trim($r['title']), 'minutes' => (int) $r['minutes']])
            ->values();

        if ($agenda->sum('minutes') > (int) ($data['duration_minutes'] ?? 0)) {
            throw ValidationException::withMessages(['agenda' => __('مجموع مدد الجدول الزمني أكبر من مدة الحدث.')]);
        }

        $data['agenda'] = $agenda->isEmpty() ? null : $agenda->all();
        $data['speaker_name'] = trim((string) ($data['speaker_name'] ?? '')) ?: null;
        $data['speaker_bio'] = trim((string) ($data['speaker_bio'] ?? '')) ?: null;

        return $data;
    }

    private function authorizeHost(Request $request, Event $event): void
    {
        abort_unless($event->isHostedBy($request->user()), 403, __('ليس لديك صلاحية على هذا الحدث.'));
    }

    /** سبب منع التعديل (أو null لو مسموح) */
    private function lockedReason(Event $event): ?string
    {
        if ($event->isCancelled()) {
            return __('هذا الحدث ملغى ولا يمكن تعديله.');
        }

        if ($event->starts_at->isPast()) {
            return __('بدأ الحدث أو انتهى، ولا يمكن تعديله.');
        }

        return null;
    }
}
