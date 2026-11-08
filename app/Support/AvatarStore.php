<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * تخزين الصور الشخصية داخل قاعدة البيانات بدل نظام الملفات،
 * حتى لا تضيع على الاستضافات التي لا تحفظ الملفات (Vercel وRender المجاني).
 * تُصغَّر الصورة إلى 256×256 فتبقى بحجم صغير.
 */
class AvatarStore
{
    private const SIZE = 256;

    public static function save(User $user, UploadedFile $file): void
    {
        [$mime, $binary] = self::process($file);

        DB::table('user_avatars')->updateOrInsert(
            ['user_id' => $user->id],
            ['mime' => $mime, 'data' => base64_encode($binary), 'updated_at' => now(), 'created_at' => now()]
        );

        self::deleteLegacy($user);
        $user->avatar_path = 'db:'.time();
    }

    public static function remove(User $user): void
    {
        DB::table('user_avatars')->where('user_id', $user->id)->delete();
        self::deleteLegacy($user);
        $user->avatar_path = null;
    }

    private static function deleteLegacy(User $user): void
    {
        if ($user->avatar_path && ! str_starts_with($user->avatar_path, 'db:')) {
            Storage::disk('public')->delete($user->avatar_path);
        }
    }

    /** @return array{0:string,1:string} */
    private static function process(UploadedFile $file): array
    {
        $raw = (string) file_get_contents($file->getRealPath());

        if (! function_exists('imagecreatefromstring')) {
            return [$file->getMimeType() ?: 'image/jpeg', $raw];
        }

        $src = @imagecreatefromstring($raw);

        if (! $src) {
            return [$file->getMimeType() ?: 'image/jpeg', $raw];
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $dst = imagecreatetruecolor(self::SIZE, self::SIZE);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), self::SIZE, self::SIZE, $side, $side);

        ob_start();
        imagejpeg($dst, null, 84);
        $out = (string) ob_get_clean();

        return ['image/jpeg', $out];
    }
}
