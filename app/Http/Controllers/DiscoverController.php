<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** صفحات عامة لمحركات البحث: الأحداث بحسب الدولة والمدينة والمجال */
class DiscoverController extends Controller
{
    public function country(Request $request, string $country): View
    {
        abort_unless(array_key_exists($country, Catalog::places()), 404);

        return $this->page($request, ['country' => $country], [
            'heading' => __('لقاءات الفريلانسرز في :p', ['p' => __($country)]),
            'subject' => __('في :p', ['p' => __($country)]),
            'crumbs' => [[__($country), null]],
        ]);
    }

    public function city(Request $request, string $country, string $city): View
    {
        abort_unless(in_array($city, Catalog::places()[$country] ?? [], true), 404);

        return $this->page($request, ['country' => $country, 'city' => $city], [
            'heading' => __('لقاءات الفريلانسرز في :p', ['p' => __($city)]),
            'subject' => __('في :p', ['p' => __($city).__('،').' '.__($country)]),
            'crumbs' => [[__($country), route('events.country', $country)], [__($city), null]],
        ]);
    }

    public function type(Request $request, string $type): View
    {
        $types = Catalog::eventTypes(false);
        abort_unless(array_key_exists($type, $types), 404);

        return $this->page($request, ['type' => $type], [
            'heading' => __('لقاءات :p للفريلانسرز', ['p' => __($types[$type])]),
            'subject' => __('في مجال «:p»', ['p' => __($types[$type])]),
            'crumbs' => [[__($types[$type]), null]],
        ]);
    }

    private function page(Request $request, array $filters, array $info): View
    {
        $events = Event::upcoming()
            ->with('host')
            ->when($request->user(), fn ($q, $u) => $q->with(['requests' => fn ($r) => $r->where('user_id', $u->id)]))
            ->withCount('acceptedRequests')
            ->where($filters)
            ->orderBy('starts_at')
            ->paginate(9)
            ->withQueryString();

        // أماكن ومجالات فيها أحداث قادمة: روابط داخلية تساعد الزحف والتنقل
        $groups = Event::upcoming()->selectRaw('country, city, type, count(*) as total')->groupBy('country', 'city', 'type')->get();

        return view('discover', $info + [
            'events' => $events,
            'filters' => $filters,
            'count' => $events->total(),
            'cities' => $this->tally($groups, 'city', $filters['country'] ?? null),
            'countries' => $this->tally($groups, 'country'),
            'typeLinks' => $this->tally($groups, 'type'),
            'types' => Catalog::eventTypes(false),
        ]);
    }

    /** @return Collection<string,int> اسم => عدد الأحداث، للأكبر أولًا */
    private function tally(Collection $groups, string $field, ?string $country = null): Collection
    {
        return $groups
            ->when($country, fn ($c) => $c->where('country', $country))
            ->filter(fn ($g) => filled($g->{$field}))
            ->groupBy($field)
            ->map(fn ($rows) => (int) $rows->sum('total'))
            ->sortDesc();
    }
}
