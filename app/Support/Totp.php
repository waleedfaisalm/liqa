<?php

namespace App\Support;

/** رموز التحقق بخطوتين (TOTP حسب RFC 6238) متوافقة مع Google Authenticator */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function code(string $secret, ?int $time = null, int $digits = 6): string
    {
        $counter = intdiv($time ?? time(), 30);
        $hash = hash_hmac('sha1', pack('N*', 0, $counter), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0xF;
        $value = (unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF) % (10 ** $digits);

        return str_pad((string) $value, $digits, '0', STR_PAD_LEFT);
    }

    /** يقبل الرمز الحالي والسابق والتالي لتعويض فرق الساعة */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $ok = false;
        for ($i = -$window; $i <= $window; $i++) {
            $ok = hash_equals(self::code($secret, time() + $i * 30), $code) || $ok;
        }

        return $ok;
    }

    public static function uri(string $secret, string $account, string $issuer = 'لقاء'): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&digits=6&period=30';
    }

    private static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    private static function base32Decode(string $s): string
    {
        $bits = '';
        foreach (str_split(strtoupper($s)) as $c) {
            $pos = strpos(self::ALPHABET, $c);
            if ($pos !== false) {
                $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
            }
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
