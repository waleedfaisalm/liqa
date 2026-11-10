<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect([
            [route('landing'), null, '1.0'],
            [route('events.index'), null, '0.9'],
            [route('contact'), null, '0.4'],
            [route('privacy'), null, '0.3'],
            [route('terms'), null, '0.3'],
        ]);

        Event::upcoming()->orderBy('starts_at')->limit(500)->get(['id', 'updated_at'])
            ->each(fn ($e) => $urls->push([route('events.show', $e), $e->updated_at?->toAtomString(), '0.7']));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as [$loc, $mod, $prio]) {
            $xml .= '  <url><loc>'.e($loc).'</loc>'.($mod ? '<lastmod>'.$mod.'</lastmod>' : '').'<priority>'.$prio.'</priority></url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** تشغيل التذكيرات من خدمة جدولة خارجية: /cron/reminders?key=CRON_SECRET */
    public function reminders(Request $request): Response
    {
        $secret = (string) env('CRON_SECRET', '');
        abort_if($secret === '' || ! hash_equals($secret, (string) ($request->query('key') ?: $request->bearerToken())), 404);

        Artisan::call('liqa:send-reminders');

        return response(trim(Artisan::output()), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
