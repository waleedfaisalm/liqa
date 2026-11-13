<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EventCalendarController extends Controller
{
    /** تنزيل ملف تقويم (ICS) للحدث — للمنظّم والمشاركين المقبولين فقط */
    public function ics(Request $request, Event $event): Response
    {
        abort_unless($event->isParticipant($request->user()), 403, 'متاح للمشاركين المقبولين فقط.');
        abort_if($event->isCancelled(), 404);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Liqa//Events//AR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@liqa',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$event->starts_at->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$event->endsAt()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape($event->title),
            'DESCRIPTION:'.$this->escape($event->description."\n\n".route('events.show', $event).(\App\Support\LiveKit::configured() ? "\n".'الغرفة المباشرة: '.route('events.room', $event) : '')),
            'LOCATION:'.$this->escape($event->placeLabel()),
            'URL:'.route('events.show', $event),
            'BEGIN:VALARM',
            'TRIGGER:-PT1H',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.$this->escape('يبدأ الحدث بعد ساعة: '.$event->title),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $body = implode("\r\n", array_map([$this, 'fold'], $lines))."\r\n";

        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="liqa-event-'.$event->id.'.ics"',
        ]);
    }

    /** تهريب الأحرف الخاصة حسب معيار iCalendar */
    private function escape(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $text);
    }

    /** الأسطر لا تتجاوز 75 بايت: تُقسَّم دون كسر أحرف UTF-8 */
    private function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out = '';
        $current = '';
        foreach (mb_str_split($line) as $char) {
            if (strlen($current.$char) > ($out === '' ? 75 : 74)) {
                $out .= ($out === '' ? '' : "\r\n ").$current;
                $current = '';
            }
            $current .= $char;
        }

        return $out.($current !== '' ? "\r\n ".$current : '');
    }
}
