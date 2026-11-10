<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Response;

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
}
