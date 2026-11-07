<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public const SUBJECTS = [
        'question' => 'استفسار عام',
        'problem' => 'مشكلة تقنية',
        'suggestion' => 'اقتراح',
        'complaint' => 'شكوى',
        'other' => 'أخرى',
    ];

    public function create(): View
    {
        return view('pages.contact', ['subjects' => self::SUBJECTS]);
    }

    public function store(Request $request): RedirectResponse
    {
        // حقل مخفي: إن عُبّئ فالمرسل برنامج آلي
        if ($request->filled('website')) {
            return redirect()->route('contact')->with('success', 'تم إرسال رسالتك. شكرًا لتواصلك.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'subject' => ['required', 'in:'.implode(',', array_keys(self::SUBJECTS))],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'name.required' => 'يرجى كتابة اسمك.',
            'email.required' => 'يرجى كتابة بريدك الإلكتروني.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'subject.required' => 'يرجى اختيار موضوع الرسالة.',
            'body.required' => 'يرجى كتابة رسالتك.',
            'body.min' => 'الرسالة قصيرة جدًا.',
            'body.max' => 'الرسالة طويلة (الحد الأقصى 2000 حرف).',
        ]);

        $message = ContactMessage::create([
            'user_id' => $request->user()?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => self::SUBJECTS[$data['subject']],
            'body' => $data['body'],
            'ip' => $request->ip(),
        ]);

        try {
            $owner = User::where('is_super_admin', true)->first();
            if ($owner) {
                Mail::send('emails.action', [
                    'heading' => 'رسالة تواصل جديدة',
                    'lines' => ['الموضوع: '.$message->subject, 'من: '.$message->name.' ('.$message->email.')'],
                    'button' => 'قراءة الرسالة',
                    'url' => route('admin.messages'),
                    'footnote' => 'وصلك هذا التنبيه لأنك مالك المنصة.',
                ], fn ($m) => $m->to($owner->email)->replyTo($message->email, $message->name)->subject('رسالة تواصل جديدة — لقاء'));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('contact')->with('success', 'تم إرسال رسالتك، وسنرد عليك على بريدك الإلكتروني في أقرب وقت.');
    }
}
