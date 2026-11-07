<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventComment;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventCommentController extends Controller
{
    /** إضافة تعليق أو سؤال على حدث (قبل انتهائه) */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $back = redirect()->to(route('events.show', $event).'#comments');

        if ($event->isCancelled() || $event->hasEnded()) {
            return $back->with('error', 'التعليقات مغلقة على هذا الحدث.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:500'],
        ], [
            'body.required' => 'اكتب تعليقك أولًا.',
            'body.min' => 'التعليق قصير جدًا.',
            'body.max' => 'التعليق طويل جدًا (الحد الأقصى 500 حرف).',
        ]);

        $user = $request->user();

        EventComment::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'body' => trim($data['body']),
        ]);

        if ($event->isHostedBy($user)) {
            // رد المنظّم: إشعار لمن علّقوا سابقًا
            $event->comments()->where('user_id', '!=', $user->id)->with('user')->get()
                ->pluck('user')->unique('id')->take(20)
                ->each(fn ($u) => UserNotification::send(
                    $u,
                    'comment_reply',
                    'ردّ المنظّم على الأسئلة في حدث «'.$event->title.'».',
                    route('events.show', $event).'#comments',
                ));
        } else {
            UserNotification::send(
                $event->host,
                'comment_new',
                $user->name.' علّق على حدث «'.$event->title.'».',
                route('events.show', $event).'#comments',
            );
        }

        return $back->with('success', 'تمت إضافة تعليقك.');
    }

    /** حذف تعليق: لصاحبه أو لمنظّم الحدث */
    public function destroy(Request $request, Event $event, EventComment $comment): RedirectResponse
    {
        abort_unless($comment->event_id === $event->id, 404);

        $user = $request->user();
        abort_unless($comment->user_id === $user->id || $event->isHostedBy($user), 403, 'لا تملك صلاحية حذف هذا التعليق.');

        $comment->delete();

        return redirect()->to(route('events.show', $event).'#comments')->with('success', 'تم حذف التعليق.');
    }
}
