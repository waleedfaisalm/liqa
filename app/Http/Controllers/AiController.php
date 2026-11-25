<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Support\Ai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    private function unavailable(): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => __('الخدمة غير متاحة حاليًا، حاول لاحقًا.')], 503);
    }

    /** كاتب أوصاف الأحداث */
    public function description(Request $request): JsonResponse
    {
        $d = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'type' => ['nullable', 'string', 'max:40'],
            'mode' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:600'],
        ]);

        $text = Ai::enabled() ? Ai::eventDescription($d['title'], $d['type'] ?? null, $d['mode'] ?? null, $d['city'] ?? null, $d['notes'] ?? null) : null;

        return $text ? response()->json(['ok' => true, 'text' => $text]) : $this->unavailable();
    }

    /** ملخص تقييمات عضو */
    public function ratings(User $user): JsonResponse
    {
        $text = Ai::enabled() ? Ai::ratingsSummary($user) : null;

        return $text ? response()->json(['ok' => true, 'text' => $text]) : $this->unavailable();
    }

    /** مساعد البلاغات (للمشرفين) */
    public function report(Report $report): JsonResponse
    {
        $text = Ai::enabled() ? Ai::analyzeReport($report) : null;

        return $text ? response()->json(['ok' => true, 'text' => $text]) : response()->json(['ok' => false, 'message' => 'الخدمة غير متاحة (تحقق من مفتاح الذكاء الاصطناعي).'], 503);
    }
}
