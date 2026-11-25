<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Event;
use App\Models\Segment;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** الشرائح المحفوظة: تعريف مجموعات أعضاء وإرسال إشعار موجّه لها */
class AdminSegmentController extends Controller
{
    private const MAX_RECIPIENTS = 1000;

    public function index(): View
    {
        $segments = Segment::latest()->get()->each(function (Segment $s) {
            $s->total = $s->members()->count();
            $s->reachable = $s->members()->where('notifications_enabled', true)->count();
        });

        return view('admin.segments', [
            'segments' => $segments,
            'specialties' => \App\Support\Catalog::specialties(),
            'cities' => User::whereNotNull('city')->where('city', '!=', '')->distinct()->pluck('city')
                ->merge(Event::whereNotNull('city')->distinct()->pluck('city'))->unique()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:60'],
            'specialty' => ['nullable', 'string', 'max:60'],
            'verified' => ['nullable', 'boolean'],
            'joined_within_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'active_within_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'inactive_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'attended_min' => ['nullable', 'integer', 'min:1', 'max:200'],
            'never_attended' => ['nullable', 'boolean'],
        ]);

        $filters = collect($data)->except('name')->filter(fn ($v) => $v !== null && $v !== '' && $v !== false && $v !== '0')->all();

        $segment = Segment::create(['name' => $data['name'], 'filters' => $filters, 'created_by' => $request->user()->id]);
        AdminLog::record($request->user(), 'segment.created', 'segment', $segment->id, $segment->name);

        return back()->with('success', 'حُفظت الشريحة «'.$segment->name.'».');
    }

    public function destroy(Request $request, Segment $segment): RedirectResponse
    {
        AdminLog::record($request->user(), 'segment.deleted', 'segment', $segment->id, $segment->name);
        $segment->delete();

        return back()->with('success', 'حُذفت الشريحة.');
    }

    public function send(Request $request, Segment $segment): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:200'],
            'link' => ['nullable', 'string', 'max:200'],
        ]);

        $link = trim((string) ($data['link'] ?? ''));
        if ($link !== '') {
            $path = parse_url($link, PHP_URL_PATH) ?: '/';
            $host = parse_url($link, PHP_URL_HOST);
            if ($host && $host !== $request->getHost()) {
                return back()->with('error', 'الرابط يجب أن يكون داخل المنصة.');
            }
            $link = url('/'.ltrim($path, '/'));
        }

        $users = $segment->members()->where('notifications_enabled', true)->limit(self::MAX_RECIPIENTS)->get(['id']);
        if ($users->isEmpty()) {
            return back()->with('error', 'لا يوجد أعضاء يستقبلون الإشعارات في هذه الشريحة.');
        }

        $now = now();
        $users->chunk(200)->each(fn ($chunk) => UserNotification::insert($chunk->map(fn ($u) => [
            'user_id' => $u->id, 'type' => 'broadcast', 'message' => $data['message'], 'url' => $link ?: null, 'created_at' => $now, 'updated_at' => $now,
        ])->all()));

        $segment->forceFill(['last_sent_at' => $now])->save();
        AdminLog::record($request->user(), 'segment.sent', 'segment', $segment->id, $segment->name, $users->count().' مستلم');

        return back()->with('success', 'أُرسل الإشعار إلى '.$users->count().' عضو.');
    }
}
