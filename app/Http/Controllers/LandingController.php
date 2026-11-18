<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class LandingController extends Controller
{
    /** الحد الأدنى لإظهار قسم الأرقام، حتى لا يبدو الموقع فارغًا في بدايته */
    private const MIN_MEMBERS = 25;
    private const MIN_EVENTS = 5;

    public function index(): View
    {
        // الأرقام الثقيلة نسبيًا تُخزَّن مؤقتًا لدقائق
        $stats = Cache::store('file')->remember('landing.stats', 300, function () {
            return [
                'events' => Event::where('status', Event::PUBLISHED)->count(),
                'cities' => Event::where('status', Event::PUBLISHED)->whereNotNull('city')->distinct()->count('city'),
                'joined' => EventRequest::where('status', EventRequest::ACCEPTED)->count(),
            ];
        });

        // عدد الأعضاء يُحسب لحظيًا (استعلام COUNT خفيف) فيزيد فور اكتمال تسجيل أي عضو،
        // وبنفس شروط ظهور العضو في دليل الأعضاء ليتطابق الرقم مع ما يراه الزائر.
        $stats['members'] = User::where('is_admin', false)
            ->whereNotNull('onboarded_at')
            ->whereNull('suspended_at')
            ->where('profile_public', true)
            ->count();

        $showStats = $stats['members'] >= self::MIN_MEMBERS && $stats['events'] >= self::MIN_EVENTS;

        $upcoming = Event::upcoming()->with('host')->orderBy('starts_at')->limit(3)->get();

        return view('landing', compact('stats', 'showStats', 'upcoming'));
    }
}
