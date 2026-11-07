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

        return view('dashboard', compact('upcoming', 'myHosted', 'myJoined', 'pending'));
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
            return redirect()->route('events.index')->with('error', 'إنشاء الأحداث الجديدة متوقف مؤقتًا من إدارة المنصة.');
        }

        return view('events.form', ['event' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! \App\Models\Setting::enabled('event_creation_enabled')) {
            return redirect()->route('events.index')->with('error', 'إنشاء الأحداث الجديدة متوقف مؤقتًا من إدارة المنصة.');
        }

        $data = $this->normalizePlace($request->validate($this->rules()));

        $event = new Event($data);
        $event->user_id = $request->user()->id;
        $event->status = Event::PUBLISHED;
        $event->save();

        return redirect()->route('events.show', $event)->with('success', 'تم نشر الحدث. يمكن للأعضاء الآن تقديم طلبات الانضمام.');
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

        return view('events.show', [
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

        return redirect()->route('events.show', $event)->with('success', 'تم حفظ التعديلات.');
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
            return redirect()->route('events.mine')->with('error', 'هذا الحدث ملغى بالفعل.');
        }

        if ($event->starts_at->isPast()) {
            return redirect()->route('events.show', $event)->with('error', 'لا يمكن حذف حدث بدأ أو انتهى، حفاظًا على التقييمات.');
        }

        $affected = $event->requests()->whereIn('status', [EventRequest::PENDING, EventRequest::ACCEPTED, EventRequest::WAITLISTED])->with('user')->get();

        if ($event->requests()->doesntExist()) {
            $event->delete();

            return redirect()->route('events.mine')->with('success', 'تم حذف الحدث.');
        }

        $event->status = Event::CANCELLED;
        $event->save();

        $affected->each(fn ($r) => UserNotification::send(
            $r->user,
            'event_cancelled',
            'تم إلغاء حدث «'.$event->title.'» من قِبل المنظّم.',
            route('events.show', $event),
        ));

        return redirect()->route('events.mine')->with('success', 'تم إلغاء الحدث وإشعار مقدّمي طلبات الانضمام.');
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
            throw ValidationException::withMessages(['city' => 'يرجى اختيار مدينة من القائمة.']);
        }

        unset($data['city_other']);

        return $data;
    }

    private function authorizeHost(Request $request, Event $event): void
    {
        abort_unless($event->isHostedBy($request->user()), 403, 'ليس لديك صلاحية على هذا الحدث.');
    }

    /** سبب منع التعديل (أو null لو مسموح) */
    private function lockedReason(Event $event): ?string
    {
        if ($event->isCancelled()) {
            return 'هذا الحدث ملغى ولا يمكن تعديله.';
        }

        if ($event->starts_at->isPast()) {
            return 'بدأ الحدث أو انتهى، ولا يمكن تعديله.';
        }

        return null;
    }
}
