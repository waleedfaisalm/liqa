<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\EventComment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['all', 'online', 'suspended', 'admins', 'unverified'], true) ? $request->query('tab') : 'all';
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->withCount('hostedEvents')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')))
            ->when($tab === 'online', fn ($q) => $q->online()->reorder()->orderByDesc('last_seen_at'))
            ->when($tab === 'suspended', fn ($q) => $q->suspended())
            ->when($tab === 'admins', fn ($q) => $q->where('is_admin', true))
            ->when($tab === 'unverified', fn ($q) => $q->whereNull('email_verified_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => User::count(),
            'online' => User::online()->count(),
            'suspended' => User::suspended()->count(),
            'admins' => User::where('is_admin', true)->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
        ];

        return view('admin.users', compact('users', 'tab', 'search', 'counts'));
    }

    public function create(): View
    {
        return view('admin.user-form');
    }

    /** إنشاء حساب جديد وتحديد صلاحيته (عضو عادي أو مدير) */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'specialty' => ['nullable', Rule::in(\App\Support\Catalog::specialties())],
            'role' => ['required', Rule::in(['member', 'admin'])],
        ], [
            'role.required' => 'يرجى اختيار الصلاحية.',
        ]);

        if ($data['role'] === 'admin' && ! $request->user()->isOwner()) {
            return back()->withInput()->withErrors(['role' => 'إنشاء حسابات المديرين من صلاحية المالك فقط.']);
        }

        $user = User::unguarded(fn () => User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'specialty' => $data['specialty'] ?? null,
            'is_admin' => $data['role'] === 'admin',
            'email_verified_at' => now(), // أنشأته الإدارة، فالبريد موثّق
        ]));

        AdminLog::record($request->user(), 'user.created', 'user', $user->id, $user->name, $user->is_admin ? 'بصلاحية مدير' : 'عضو عادي');

        return redirect()->route('admin.users')->with('success', 'تم إنشاء حساب '.$user->name.($user->is_admin ? ' بصلاحية مدير.' : ' كعضو عادي.'));
    }

    /** صفحة تفاصيل المستخدم */
    public function show(User $user): View
    {
        $user->loadCount('hostedEvents');

        return view('admin.user', [
            'u' => $user,
            'hosted' => $user->hostedEvents()->withCount('acceptedRequests')->orderByDesc('starts_at')->limit(8)->get(),
            'joined' => $user->eventRequests()->where('status', 'accepted')->with('event.host')->latest()->limit(8)->get()->filter(fn ($r) => $r->event),
            'ratings' => $user->ratingsReceived()->with(['rater', 'event'])->latest()->limit(8)->get(),
            'ratingAvg' => $user->averageRating(),
            'reportsAgainst' => Report::where('target_type', 'user')->where('target_id', $user->id)->with('reporter')->latest()->get(),
            'reportsFiled' => Report::where('reporter_id', $user->id)->count(),
            'commentsCount' => EventComment::where('user_id', $user->id)->count(),
            'logs' => AdminLog::where('target_type', 'user')->where('target_id', $user->id)->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    /** إيقاف الحساب (مع سبب ومدة اختيارية) أو إعادة تفعيله */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'لا يمكنك إيقاف حسابك الخاص.');
        }

        if ($user->isOwner()) {
            return back()->with('error', 'حساب المالك محمي ولا يمكن إيقافه.');
        }

        if ($user->is_admin && ! $request->user()->isOwner()) {
            return back()->with('error', 'إيقاف حسابات المديرين من صلاحية المالك فقط.');
        }

        if ($user->isSuspended()) {
            $user->forceFill(['suspended_at' => null, 'suspended_until' => null, 'suspension_reason' => null])->save();
            AdminLog::record($request->user(), 'user.unsuspended', 'user', $user->id, $user->name);

            return back()->with('success', 'تمت إعادة تفعيل حساب '.$user->name.'.');
        }

        $data = $request->validate([
            'duration' => ['nullable', Rule::in(['permanent', '1', '3', '7', '30'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $days = ($data['duration'] ?? 'permanent') === 'permanent' ? null : (int) $data['duration'];

        $user->forceFill([
            'suspended_at' => now(),
            'suspended_until' => $days ? now()->addDays($days) : null,
            'suspension_reason' => trim((string) ($data['reason'] ?? '')) ?: null,
        ])->save();

        // إنهاء جلساته الحالية فورًا
        DB::table('sessions')->where('user_id', $user->id)->delete();

        AdminLog::record(
            $request->user(), 'user.suspended', 'user', $user->id, $user->name,
            ($days ? "لمدة {$days} يوم" : 'إيقاف دائم').($user->suspension_reason ? ' — السبب: '.$user->suspension_reason : '')
        );

        return back()->with('success', 'تم إيقاف حساب '.$user->name.($days ? " لمدة {$days} يوم." : ' إيقافًا دائمًا.'));
    }

    /** إعادة تعيين كلمة مرور عضو: تُولَّد كلمة مؤقتة تُعرض مرة واحدة للمدير ليسلّمها للعضو */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $me = $request->user();

        if ($user->is($me)) {
            return back()->with('error', 'غيّر كلمة مرورك من صفحة الإعدادات.');
        }

        if ($user->isOwner()) {
            return back()->with('error', 'حساب المالك محمي.');
        }

        if ($user->is_admin && ! $me->isOwner()) {
            return back()->with('error', 'إعادة تعيين كلمة مرور المديرين من صلاحية المالك فقط.');
        }

        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $temp = '';
        for ($i = 0; $i < 12; $i++) {
            $temp .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $user->forceFill(['password' => $temp, 'remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        AdminLog::record($me, 'user.password_reset', 'user', $user->id, $user->name);

        return back()->with('temp_password', $temp)->with('success', 'تمت إعادة تعيين كلمة مرور '.$user->name.'. سلّمه الكلمة المؤقتة ونبّهه إلى تغييرها من الإعدادات.');
    }

    /** منح صلاحية الإدارة أو سحبها */
    public function role(Request $request, User $user): RedirectResponse
    {
        if (! $request->user()->isOwner()) {
            return back()->with('error', 'تغيير الصلاحيات من صلاحية المالك فقط.');
        }

        if ($user->is($request->user()) || $user->isOwner()) {
            return back()->with('error', 'لا يمكن تغيير صلاحية حساب المالك.');
        }

        $user->forceFill(['is_admin' => ! $user->is_admin, 'admin_permissions' => null])->save();
        AdminLog::record($request->user(), $user->is_admin ? 'user.role_granted' : 'user.role_revoked', 'user', $user->id, $user->name);

        return back()->with('success', $user->is_admin ? $user->name.' أصبح مديرًا.' : 'تم سحب صلاحية الإدارة من '.$user->name.'.');
    }

    /** تعديل صلاحيات أقسام لوحة الإدارة لمدير (للمالك فقط) */
    public function permissions(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        if (! $user->is_admin || $user->isOwner()) {
            return back()->with('error', 'يمكن تحديد الصلاحيات للمديرين فقط.');
        }

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(User::PERMISSIONS))],
        ]);

        $perms = array_values(array_unique($data['permissions'] ?? []));
        $user->forceFill(['admin_permissions' => $perms])->save();

        $labels = array_map(fn ($k) => User::PERMISSIONS[$k], $perms);
        AdminLog::record($request->user(), 'user.permissions', 'user', $user->id, $user->name, $labels ? 'الأقسام: '.implode('، ', $labels) : 'بدون أي قسم');

        return back()->with('success', 'تم تحديث صلاحيات '.$user->name.'.');
    }
}
