<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedEventController extends Controller
{
    /** صفحة الأحداث المحفوظة */
    public function index(Request $request): View
    {
        $user = $request->user();

        $events = $user->savedEvents()
            ->with(['host', 'requests' => fn ($q) => $q->where('user_id', $user->id)])
            ->withCount('acceptedRequests')
            ->orderBy('starts_at')
            ->get();

        [$past, $upcoming] = $events->partition(fn (Event $e) => $e->isCancelled() || $e->hasEnded());

        return view('saved', ['upcoming' => $upcoming->values(), 'past' => $past->values()]);
    }

    /** حفظ الحدث أو إزالته من المحفوظات */
    public function toggle(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($event->isHostedBy($user)) {
            return $this->respond($request, false, 'لا حاجة لحفظ حدثك الخاص.', 422);
        }

        $changes = $user->savedEvents()->toggle($event->id);
        $saved = count($changes['attached']) > 0;

        return $this->respond($request, $saved, $saved ? 'تمت إضافة الحدث إلى المحفوظات.' : 'تمت إزالة الحدث من المحفوظات.');
    }

    private function respond(Request $request, bool $saved, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved, 'message' => $message], $status);
        }

        return back()->with($status === 200 ? 'success' : 'error', $message);
    }
}
