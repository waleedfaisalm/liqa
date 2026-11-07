<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** بروفايلي */
    public function me(Request $request): View
    {
        return $this->render($request, $request->user(), true);
    }

    /** بروفايل يوزر تاني */
    public function show(Request $request, User $user): View|RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()->route('profile');
        }

        return $this->render($request, $user, false);
    }

    private function render(Request $request, User $user, bool $isMe): View
    {
        $tab = $request->query('tab') === 'ratings' ? 'ratings' : 'events';

        $allRatings = $user->ratingsReceived()->with(['rater', 'event'])->latest()->get();

        $upcoming = $user->hostedEvents()
            ->upcoming()
            ->withCount('acceptedRequests')
            ->orderBy('starts_at')
            ->get();

        $hostedPast = $user->hostedEvents()
            ->where('status', Event::PUBLISHED)
            ->withCount('acceptedRequests')
            ->get()
            ->filter(fn ($e) => $e->hasEnded());

        $attendedPast = $user->eventRequests()
            ->where('status', EventRequest::ACCEPTED)
            ->with(['event' => fn ($q) => $q->withCount('acceptedRequests')])
            ->get()
            ->pluck('event')
            ->filter(fn ($e) => $e && $e->status === Event::PUBLISHED && $e->hasEnded());

        $past = $hostedPast->concat($attendedPast)->unique('id')->sortByDesc('starts_at')->values();

        $distribution = collect(range(5, 1))->mapWithKeys(fn ($n) => [$n => $allRatings->where('stars', $n)->count()]);

        return view('profile', [
            'profile' => $user,
            'isMe' => $isMe,
            'tab' => $tab,
            'upcoming' => $upcoming,
            'past' => $past,
            'hostedCount' => $user->hostedEvents()->where('status', Event::PUBLISHED)->count(),
            'average' => $user->averageRating(),
            'ratings' => $allRatings,
            'ratingsCount' => $allRatings->count(),
            'distribution' => $distribution,
            'topTags' => $allRatings->pluck('tags')->flatten()->filter()->countBy()->sortDesc()->take(8),
        ]);
    }
}
