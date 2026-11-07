<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Event;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportController extends Controller
{
    public function __invoke(Request $request, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['users', 'events', 'reports'], true), 404);

        $sets = [
            'users' => [
                ['الاسم', 'البريد', 'التخصص', 'الدور', 'الحالة', 'موثّق', 'تاريخ الانضمام'],
                fn () => User::orderBy('id')->cursor(),
                fn (User $u) => [$u->name, $u->email, $u->specialty, $u->isOwner() ? 'المالك' : ($u->is_admin ? 'مدير' : 'عضو'), $u->isSuspended() ? 'موقوف' : 'نشط', $u->hasVerifiedEmail() ? 'نعم' : 'لا', $u->created_at],
            ],
            'events' => [
                ['العنوان', 'المجال', 'النمط', 'الدولة', 'المدينة', 'الموعد', 'السعة', 'الحالة', 'المنظّم'],
                fn () => Event::with('host')->orderBy('id')->cursor(),
                fn (Event $e) => [$e->title, $e->typeLabel(), $e->modeLabel(), $e->country, $e->city, $e->starts_at, $e->capacity, $e->status, $e->host?->name],
            ],
            'reports' => [
                ['السبب', 'نوع الهدف', 'رقم الهدف', 'المُبلِّغ', 'التفاصيل', 'الحالة', 'التاريخ'],
                fn () => Report::with('reporter')->orderBy('id')->cursor(),
                fn (Report $r) => [Report::REASONS[$r->reason] ?? $r->reason, $r->target_type === 'user' ? 'مستخدم' : 'حدث', $r->target_id, $r->reporter?->name, $r->details, $r->status, $r->created_at],
            ],
        ];

        [$head, $source, $map] = $sets[$type];
        AdminLog::record($request->user(), 'export.csv', null, null, null, 'تصدير: '.$type);

        // منع حقن الصيغ في Excel
        $safe = function ($v) {
            $v = (string) $v;

            return $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'".$v : $v;
        };

        return response()->streamDownload(function () use ($head, $source, $map, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM ليقرأ Excel العربية
            fputcsv($out, $head);
            foreach ($source() as $row) {
                fputcsv($out, array_map($safe, $map($row)));
            }
            fclose($out);
        }, "liqa-{$type}-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
