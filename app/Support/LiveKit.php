<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/** تكامل مع LiveKit: توليد رموز الدخول وطرد مشارك (بدون مكتبات إضافية). */
class LiveKit
{
    public static function configured(): bool
    {
        return filled(config('services.livekit.url'))
            && filled(config('services.livekit.key'))
            && filled(config('services.livekit.secret'));
    }

    public static function url(): string
    {
        return (string) config('services.livekit.url');
    }

    /** رمز دخول لغرفة بصلاحيات: نشر الصوت والفيديو والاستماع (والإدارة للمنظّم) */
    public static function token(string $room, string $identity, string $name, array $metadata = [], bool $admin = false, int $ttl = 3 * 3600): string
    {
        $now = time();

        return self::sign([
            'iss' => config('services.livekit.key'),
            'sub' => $identity,
            'name' => $name,
            'nbf' => $now - 10,
            'exp' => $now + $ttl,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
            'video' => [
                'room' => $room,
                'roomJoin' => true,
                'canPublish' => true,
                'canSubscribe' => true,
                'canPublishData' => true,
                'canUpdateOwnMetadata' => true, // لرفع اليد عبر خصائص المشارك
                'roomAdmin' => $admin,
            ],
        ]);
    }

    /** طرد مشارك من الغرفة (يتطلب صلاحية إدارة على الخادم) */
    public static function remove(string $room, string $identity): bool
    {
        return self::call($room, 'RemoveParticipant', ['room' => $room, 'identity' => $identity]);
    }

    /** كتم مسار صوتي لمشارك من الخادم (لا يستطيع تجاهله) */
    public static function mute(string $room, string $identity, string $trackSid): bool
    {
        return self::call($room, 'MutePublishedTrack', ['room' => $room, 'identity' => $identity, 'track_sid' => $trackSid, 'muted' => true]);
    }

    /** تحديث البيانات الوصفية لمشارك متصل (مثلاً ترقيته إلى منظّم مساعد فورًا) */
    public static function updateMetadata(string $room, string $identity, array $metadata): bool
    {
        return self::call($room, 'UpdateParticipant', [
            'room' => $room,
            'identity' => $identity,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private static function call(string $room, string $method, array $body): bool
    {
        $http = preg_replace('#^wss?://#', 'https://', rtrim(self::url(), '/'));

        $admin = self::sign([
            'iss' => config('services.livekit.key'),
            'sub' => 'liqa-server',
            'nbf' => time() - 10,
            'exp' => time() + 60,
            'video' => ['room' => $room, 'roomAdmin' => true],
        ]);

        try {
            return Http::withToken($admin)->acceptJson()->timeout(8)
                ->post($http.'/twirp/livekit.RoomService/'.$method, $body)->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function sign(array $payload): string
    {
        $b64 = fn (string $d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');

        $head = $b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = $b64(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $sig = $b64(hash_hmac('sha256', $head.'.'.$body, (string) config('services.livekit.secret'), true));

        return $head.'.'.$body.'.'.$sig;
    }
}
