<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserNotification;

/** تقييم خطورة الحسابات الجديدة بقواعد شفافة (لا يمنع التسجيل؛ يساعد المشرف على المراجعة) */
class RiskScorer
{
    /** نطاقات بريد مؤقت شائعة */
    public const DISPOSABLE = [
        'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'guerrillamail.org', 'sharklasers.com', 'grr.la', 'guerrillamailblock.com',
        '10minutemail.com', '10minutemail.net', 'tempmail.com', 'temp-mail.org', 'temp-mail.io', 'tempmail.net', 'tempmailo.com', 'tempail.com',
        'throwawaymail.com', 'trashmail.com', 'trashmail.net', 'trashmail.de', 'yopmail.com', 'yopmail.net', 'yopmail.fr', 'getnada.com', 'nada.email',
        'dispostable.com', 'fakeinbox.com', 'maildrop.cc', 'mailnesia.com', 'mintemail.com', 'mohmal.com', 'emailondeck.com', 'burnermail.io',
        'spamgourmet.com', 'mytemp.email', 'discard.email', 'discardmail.com', 'moakt.com', 'tmpmail.org', 'tmpmail.net', 'tmails.net',
        'mail.tm', 'internxt.com', 'inboxkitten.com', 'emailfake.com', 'fakemail.net', 'fakemailgenerator.com', 'dropmail.me', 'mailcatch.com',
        'spambox.us', 'incognitomail.com', 'mailforspam.com', 'jetable.org', 'anonbox.net', '33mail.com', 'eyepaste.com', 'mvrht.net',
        'cuvox.de', 'dayrep.com', 'einrot.com', 'fleckens.hu', 'gustr.com', 'jourrapide.com', 'rhyta.com', 'superrito.com', 'teleworm.us', 'armyspy.com',
    ];

    public static function level(int $score): array
    {
        return match (true) {
            $score >= 60 => ['مرتفعة', 'liqa-badge--red'],
            $score >= 30 => ['متوسطة', 'liqa-badge--pink'],
            default => ['منخفضة', 'liqa-badge--green'],
        };
    }

    public static function hashIp(?string $ip): ?string
    {
        return $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null;
    }

    /** @return array{score:int, flags:list<array{label:string,points:int}>} */
    public static function score(User $user, ?string $ipHash = null, bool $google = false): array
    {
        $flags = [];
        $add = function (string $label, int $points) use (&$flags) {
            $flags[] = ['label' => $label, 'points' => $points];
        };

        $email = strtolower((string) $user->email);
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if (in_array($domain, self::DISPOSABLE, true)) {
            $add('بريد مؤقت ('.$domain.')', 50);
        }

        if (str_contains($local, '+')) {
            $add('بريد بنمط alias (+)', 8);
        }

        $letters = preg_replace('/[^a-z]/', '', $local);
        $digits = preg_match_all('/\d/', $local);
        if ($digits >= 6) {
            $add('أرقام كثيرة في البريد', 15);
        }
        if (strlen($letters) >= 10) {
            $vowels = preg_match_all('/[aeiouy]/', $letters);
            if ($vowels / strlen($letters) < 0.2 || preg_match('/[^aeiouy]{6,}/', $letters)) {
                $add('بريد يبدو عشوائيًا', 15);
            }
        }

        $name = trim((string) $user->name);
        if (preg_match('~https?:|www\.|@|\.(com|net|ru|xyz)\b~i', $name)) {
            $add('الاسم يحتوي رابطًا أو بريدًا', 30);
        }
        if (preg_match_all('/\d/', $name) >= 3) {
            $add('أرقام كثيرة في الاسم', 10);
        }
        if (mb_strlen($name) < 2) {
            $add('اسم قصير جدًا', 10);
        }
        if (preg_match('/(.)\1{3,}/u', $name)) {
            $add('أحرف مكررة في الاسم', 10);
        }

        if ($ipHash) {
            $same = User::where('signup_ip_hash', $ipHash)->where('id', '!=', $user->id)->where('created_at', '>=', now()->subDay())->count();
            if ($same >= 2) {
                $add('عدة حسابات من الشبكة نفسها خلال 24 ساعة ('.($same + 1).')', 35);
            } elseif ($same === 1) {
                $add('حساب آخر من الشبكة نفسها خلال 24 ساعة', 15);
            }
        }

        $score = array_sum(array_column($flags, 'points'));

        if ($google) {
            $flags[] = ['label' => 'تسجيل عبر Google (بريد موثّق)', 'points' => -25];
            $score -= 25;
        }

        return ['score' => max(0, min(100, $score)), 'flags' => $flags];
    }

    /** يقيّم حسابًا جديدًا ويحفظ النتيجة وينبّه المشرفين عند ارتفاع الخطورة. لا يوقف التسجيل أبدًا. */
    public static function assess(User $user, ?string $ip = null, bool $google = false): void
    {
        if (! Automation::on('auto_fake_detection')) {
            return;
        }

        try {
            $hash = self::hashIp($ip) ?? $user->signup_ip_hash;
            $r = self::score($user, $hash, $google);
            $user->forceFill(['signup_ip_hash' => $hash, 'risk_score' => $r['score'], 'risk_flags' => $r['flags']])->save();

            if ($r['score'] >= max(30, Automation::int('auto_risk_threshold'))) {
                $admins = User::where('is_admin', true)->orWhere('is_super_admin', true)->get()
                    ->filter(fn (User $u) => $u->isOwner() || $u->hasPermission('users'));
                foreach ($admins as $admin) {
                    UserNotification::send($admin, 'risk_alert', 'حساب جديد بخطورة مرتفعة: «'.$user->name.'» (الدرجة '.$r['score'].'). راجعه من لوحة الإدارة.', route('admin.users.show', $user));
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** يعيد تقييم الحساب دون إشعار (للفحص الشامل أو الزر اليدوي) */
    public static function rescore(User $user): void
    {
        $r = self::score($user, $user->signup_ip_hash, $user->google_id !== null);
        $user->forceFill(['risk_score' => $r['score'], 'risk_flags' => $r['flags']])->save();
    }
}
