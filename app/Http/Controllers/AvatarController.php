<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AvatarController extends Controller
{
    public function show(int $user): Response
    {
        $row = DB::table('user_avatars')->where('user_id', $user)->first();

        abort_unless($row, 404);

        return response(base64_decode($row->data), 200, [
            'Content-Type' => $row->mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
