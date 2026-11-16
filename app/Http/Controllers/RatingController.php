<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Rating;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RatingController extends Controller
{
    /** صفحة تقييم مشارك */
    public function create(Request $request, Event $event, User $user): View|RedirectResponse
    {
        if ($error = $this->blockedReason($request->user(), $event, $user)) {
            return redirect()->to(route('events.show', $event).'#ratings')->with('error', $error);
        }

        return view('events.rate', ['event' => $event->load('host'), 'ratee' => $user]);
    }

    /** حفظ التقييم: بين المشاركين (منظّم أو مقبول) بعد انتهاء الحدث */
    public function store(Request $request, Event $event, User $user): RedirectResponse
    {
        $rater = $request->user();

        if ($error = $this->blockedReason($rater, $event, $user)) {
            return redirect()->to(route('events.show', $event).'#ratings')->with('error', $error);
        }

        $data = $request->validate([
            'stars' => ['required', 'integer', 'between:1,5'],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['string', Rule::in(Rating::TAGS)],
            'comment' => ['nullable', 'string', 'max:300'],
        ], [
            'stars.required' => __('يرجى اختيار عدد النجوم.'),
            'tags.max' => __('يمكنك اختيار 5 صفات كحد أقصى.'),
        ]);

        Rating::create([
            'event_id' => $event->id,
            'rater_id' => $rater->id,
            'ratee_id' => $user->id,
            'stars' => $data['stars'],
            'tags' => array_values($data['tags'] ?? []),
            'comment' => $data['comment'] ?? null,
        ]);

        UserNotification::send(
            $user,
            'rating_received',
            $rater->name.' قيّمك في حدث «'.$event->title.'».',
            route('profile'),
        );

        return redirect()->to(route('events.show', $event).'#ratings')
            ->with('success', __('تم حفظ تقييمك لـ :v1. شكرًا لمساهمتك.', ['v1' => $user->name]));
    }

    private function blockedReason(User $rater, Event $event, User $ratee): ?string
    {
        if ($rater->is($ratee)) {
            return __('لا يمكنك تقييم نفسك.');
        }

        if ($event->isCancelled() || ! $event->hasEnded()) {
            return __('يمكن التقييم بعد انتهاء الحدث فقط.');
        }

        if (! $event->isParticipant($rater) || ! $event->isParticipant($ratee)) {
            return __('التقييم متاح بين المشاركين في الحدث فقط.');
        }

        if (! $event->canRate($rater, $ratee)) {
            return __('لقد قيّمت هذا المشارك في هذا الحدث مسبقًا.');
        }

        return null;
    }
}
