<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Rating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRatingController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $ratings = Rating::with(['rater', 'ratee', 'event'])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where(fn ($w) => $w->where('comment', 'like', $like)
                    ->orWhereHas('rater', fn ($u) => $u->where('name', 'like', $like))
                    ->orWhereHas('ratee', fn ($u) => $u->where('name', 'like', $like)));
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ratings', compact('ratings', 'search'));
    }

    public function destroy(Request $request, Rating $rating): RedirectResponse
    {
        $rating->loadMissing(['rater', 'ratee']);

        AdminLog::record(
            $request->user(), 'rating.deleted', 'user', $rating->ratee_id, $rating->ratee?->name,
            'تقييم '.$rating->stars.'/5 من '.($rating->rater?->name ?? '—').($rating->comment ? ': '.mb_substr($rating->comment, 0, 100) : '')
        );

        $rating->delete();

        return back()->with('success', 'تم حذف التقييم.');
    }
}
