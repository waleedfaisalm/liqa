<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** يمنع الانضمام الجديد للأحداث للعضو المقيّد من الإدارة بسبب تكرار الغياب */
class BlockRestrictedJoin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isJoinRestricted()) {
            return back()->with('error', __('انضمامك للأحداث مقيّد مؤقتًا حتى :d بسبب تكرار الغياب. يمكنك العودة بعد انتهاء المدة.', ['d' => $user->join_restricted_until->translatedFormat('j F Y')]));
        }

        return $next($request);
    }
}
