<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\User;
use App\Support\Badges;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembersController extends Controller
{
    private const SORTS = [
        'top' => 'الأعلى تقييمًا',
        'active' => 'الأكثر نشاطًا',
        'new' => 'الأحدث انضمامًا',
    ];

    /** دليل الأعضاء: بحث بالاسم أو التخصص أو النبذة */
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $specialty = (string) $request->query('specialty', '');
        $sort = array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'top';

        $hostedSql = "(select count(*) from events where events.user_id = users.id and events.status = '".Event::PUBLISHED."' and events.starts_at < ?)";
        $attendedSql = "(select count(*) from event_requests join events on events.id = event_requests.event_id where event_requests.user_id = users.id and event_requests.status = '".EventRequest::ACCEPTED."' and events.status = '".Event::PUBLISHED."' and events.starts_at < ?)";
        $now = now();

        $query = User::query()
            ->whereNotNull('onboarded_at')
            ->whereNull('suspended_at')
            ->where('profile_public', true)
            ->withAvg('ratingsReceived as avg_rating', 'stars')
            ->withCount('ratingsReceived as ratings_count')
            ->selectRaw($hostedSql.' as hosted_count', [$now])
            ->selectRaw($attendedSql.' as attended_count', [$now]);

        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], mb_strtolower($q)).'%';
            $query->where(fn ($w) => $w
                ->whereRaw('lower(name) like ?', [$like])
                ->orWhereRaw('lower(coalesce(specialty, \'\')) like ?', [$like])
                ->orWhereRaw('lower(coalesce(bio, \'\')) like ?', [$like]));
        }

        if ($specialty !== '' && in_array($specialty, Catalog::specialties(false), true)) {
            $query->where('specialty', $specialty);
        }

        match ($sort) {
            'active' => $query->orderByRaw('('.$hostedSql.' + '.$attendedSql.') desc', [$now, $now]),
            'new' => $query->orderByDesc('created_at'),
            default => $query->orderByRaw('coalesce((select avg(stars) from ratings where ratings.ratee_id = users.id), 0) desc')
                ->orderByRaw('(select count(*) from ratings where ratings.ratee_id = users.id) desc'),
        };

        $members = $query->orderBy('name')->paginate(12)->withQueryString();

        $members->getCollection()->transform(function (User $u) {
            $u->setAttribute('badge_list', Badges::fromStats([
                'hosted' => (int) $u->hosted_count,
                'attended' => (int) $u->attended_count,
                'ratings' => (int) $u->ratings_count,
                'avg' => $u->avg_rating,
                'user_id' => $u->id,
                'complete' => (bool) ($u->avatar_path && $u->bio && $u->specialty),
            ]));

            return $u;
        });

        return view('members', [
            'members' => $members,
            'q' => $q,
            'specialty' => $specialty,
            'sort' => $sort,
            'sorts' => self::SORTS,
            'specialties' => Catalog::specialties(false),
        ]);
    }
}
