<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->isOnboarded()) {
            return redirect()->route('landing');
        }

        return view('onboarding');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'specialty' => ['required', 'string', Rule::in(\App\Support\Catalog::specialties())],
            'bio' => ['nullable', 'string', 'max:300'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $user->specialty = $data['specialty'];
        $user->bio = $data['bio'] ?? null;

        if ($request->hasFile('avatar')) {
            \App\Support\AvatarStore::save($user, $request->file('avatar'));
        }

        $user->onboarded_at = now();
        $user->save();

        return redirect()->route('landing')
            ->with('success', __('تم إعداد ملفك الشخصي بنجاح. يمكنك الآن تصفّح الأحداث ونشر أول لقاء.'));
    }
}
