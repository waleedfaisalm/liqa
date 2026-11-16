<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRequest;
use App\Models\Report;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'specialty' => ['required', 'string', Rule::in(\App\Support\Catalog::specialties(false))],
            'bio' => ['nullable', 'string', 'max:300'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $user->name = $data['name'];
        $user->specialty = $data['specialty'];
        $user->bio = $data['bio'] ?? null;
        $user->notifications_enabled = $request->boolean('notifications_enabled');
        $user->profile_public = $request->boolean('profile_public');

        if ($request->hasFile('avatar')) {
            \App\Support\AvatarStore::save($user, $request->file('avatar'));
        } elseif ($request->boolean('remove_avatar')) {
            \App\Support\AvatarStore::remove($user);
        }

        $user->save();

        return redirect()->route('settings')->with('success', __('تم حفظ التغييرات.'));
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $request->user()->update(['password' => $request->input('password')]);

        return redirect()->route('settings')->with('success', __('تم تغيير كلمة السر.'));
    }

    /** حذف الحساب نهائيًا بعد تأكيد كلمة السر */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->is_admin) {
            return back()->with('error', __('حسابات الإدارة لا تُحذف من هنا. تواصل مع مالك المنصة.'));
        }

        $request->validateWithBag('delete', [
            'delete_password' => ['required', 'current_password'],
        ], [
            'delete_password.required' => __('أدخل كلمة السر لتأكيد الحذف.'),
            'delete_password.current_password' => __('كلمة السر غير صحيحة.'),
        ]);

        DB::transaction(function () use ($user) {
            // إبلاغ المشاركين بإلغاء الأحداث القادمة التي ينظّمها
            Event::upcoming()->where('user_id', $user->id)->get()->each(function (Event $event) use ($user) {
                EventRequest::where('event_id', $event->id)->whereIn('status', [EventRequest::ACCEPTED, EventRequest::PENDING, EventRequest::WAITLISTED])
                    ->with('user')->get()->each(fn ($r) => $r->user && UserNotification::send(
                        $r->user, 'event_cancelled', 'أُلغي الحدث «'.$event->title.'» لأن منظّمه حذف حسابه.', route('events.index')
                    ));
            });

            Report::where('target_type', 'user')->where('target_id', $user->id)->delete();
            $this->deleteAvatar($user);
            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('success', __('تم حذف حسابك وبياناته نهائيًا.'));
    }

    private function deleteAvatar(User $user): void
    {
        if ($user->avatar_path && ! str_starts_with($user->avatar_path, 'db:')) {
            Storage::disk('public')->delete($user->avatar_path);
        }
    }
}
