<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\EventComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCommentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $comments = EventComment::with(['user', 'event'])
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $w->where('body', 'like', $like)->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
            }))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.comments', compact('comments', 'search'));
    }

    public function destroy(Request $request, EventComment $comment): RedirectResponse
    {
        $comment->loadMissing(['user', 'event']);

        AdminLog::record(
            $request->user(), 'comment.deleted', 'user', $comment->user_id, $comment->user?->name,
            'تعليق على «'.($comment->event?->title ?? 'حدث محذوف').'»: '.mb_substr($comment->body, 0, 100)
        );

        $comment->delete();

        return back()->with('success', 'تم حذف التعليق.');
    }
}
