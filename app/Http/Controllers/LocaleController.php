<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function set(Request $request, string $locale)
    {
        abort_unless(in_array($locale, SetLocale::LOCALES, true), 404);

        $back = url()->previous();
        $host = parse_url($back, PHP_URL_HOST);
        if (! $host || $host !== $request->getHost()) {
            $back = route('landing');
        }

        return redirect($back)->withCookie(cookie('liqa_locale', $locale, 60 * 24 * 365));
    }
}
