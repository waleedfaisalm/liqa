@extends('layouts.admin')

@section('title', $u->name)

@section('content')
    @php
        $me = auth()->user();
        $canSuspend = ! $u->is($me) && ! $u->isOwner() && ($me->isOwner() || ! $u->is_admin);
        $canChangeRole = $me->isOwner() && ! $u->is($me) && ! $u->isOwner();
        $canResetPassword = ! $u->is($me) && ! $u->isOwner() && ($me->isOwner() || ! $u->is_admin);
    @endphp

    <a href="{{ route('admin.users') }}" class="small text-decoration-none d-inline-block mb-3">← قائمة المستخدمين</a>

    <div class="liqa-card p-3 p-md-4 mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="avatar-wrap">
                <x-avatar :user="$u" :size="72" />
                @if ($u->isOnline()) <span class="presence-dot" title="متصل الآن"></span> @endif
            </span>
            <div class="flex-grow-1">
                <h1 class="h3 liqa-title mb-1">{{ $u->name }}</h1>
                <div class="small text-secondary" dir="ltr" style="text-align:right">{{ $u->email }}</div>
                <div class="d-flex flex-wrap gap-1 mt-2">
                    @if ($u->isOwner()) <span class="liqa-badge liqa-badge--sky">المالك</span> @elseif ($u->is_admin) <span class="liqa-badge">مدير</span> @endif
                    @if ($u->isSuspended()) <span class="liqa-badge liqa-badge--red">موقوف</span> @endif
                    @unless ($u->hasVerifiedEmail()) <span class="liqa-badge liqa-badge--pink">غير موثّق</span> @endunless
                    @if ($u->specialty) <span class="liqa-badge liqa-badge--sky">{{ $u->specialty }}</span> @endif
                </div>
            </div>
            <div class="small text-secondary">
                <div>انضم في {{ $u->created_at->translatedFormat('j F Y') }}</div>
                <div>
                    @if ($u->isOnline()) <span class="text-success-emphasis fw-bold">متصل الآن</span>
                    @elseif ($u->last_seen_at) آخر ظهور {{ $u->last_seen_at->diffForHumans() }}
                    @else لم يظهر بعد @endif
                </div>
                <a href="{{ route('users.show', $u) }}" target="_blank" class="d-inline-block mt-1">عرض الملف العام</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['أحداث نظّمها', $u->hosted_events_count],
            ['أحداث شارك فيها', $joined->count()],
            ['متوسط التقييم', $ratingAvg ? number_format($ratingAvg, 1).' / 5' : '—'],
            ['تعليقات', $commentsCount],
            ['بلاغات ضدّه', $reportsAgainst->count()],
            ['بلاغات قدّمها', $reportsFiled],
        ] as [$label, $num])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-tile h-100"><div class="stat-tile__num">{{ $num }}</div><div class="stat-tile__label fw-bold text-white">{{ $label }}</div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-5 order-lg-2">
            @if ($canResetPassword)
            <div class="liqa-card p-3 p-md-4 mb-3" id="password">
                <h2 class="h5 liqa-title mb-2">كلمة المرور</h2>
                @if (session('temp_password'))
                    <div class="alert alert-warning mb-3">
                        <div class="small mb-1">الكلمة المؤقتة (تُعرض مرة واحدة فقط):</div>
                        <div class="fw-bold fs-5 user-select-all" dir="ltr">{{ session('temp_password') }}</div>
                    </div>
                @endif
                <p class="small text-secondary">إن نسي العضو كلمة مروره ولم يصله البريد، ولّد له كلمة مؤقتة وسلّمها له بوسيلة آمنة. سيُسجَّل خروجه من كل الأجهزة.</p>
                <form method="POST" action="{{ route('admin.users.password', $u) }}" onsubmit="return confirm('توليد كلمة مرور مؤقتة جديدة لهذا العضو؟ ستتوقف كلمة مروره الحالية عن العمل.')">
                    @csrf @method('PATCH')
                    <button class="btn btn-liqa-outline w-100">إعادة تعيين كلمة المرور</button>
                </form>
            </div>
            @endif

            @if ($canSuspend || $canChangeRole || $u->isSuspended())
            <div class="liqa-card p-3 p-md-4 mb-3" id="suspend">
                <h2 class="h5 liqa-title mb-3">إدارة الحساب</h2>

                @if ($u->isSuspended())
                    <div class="alert alert-danger mb-3">
                        <div class="fw-bold mb-1">الحساب موقوف</div>
                        <div class="small">السبب: {{ $u->suspension_reason ?: 'لم يُذكر سبب.' }}</div>
                        <div class="small">المدة: {{ $u->suspended_until ? 'حتى '.$u->suspended_until->translatedFormat('j F Y، g:i a') : 'دائم' }}</div>
                    </div>
                    @if ($canSuspend)
                    <form method="POST" action="{{ route('admin.users.suspend', $u) }}" onsubmit="return confirm('إعادة تفعيل هذا الحساب؟')">
                        @csrf @method('PATCH')
                        <button class="btn btn-liqa w-100">إعادة تفعيل الحساب</button>
                    </form>
                    @endif
                @elseif ($canSuspend)
                    <form method="POST" action="{{ route('admin.users.suspend', $u) }}" onsubmit="return confirm('إيقاف هذا الحساب ومنعه من الدخول؟')">
                        @csrf @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label" for="duration">مدة الإيقاف</label>
                            <select id="duration" name="duration" class="form-select">
                                <option value="permanent">دائم حتى إعادة التفعيل</option>
                                <option value="1">يوم واحد</option>
                                <option value="3">٣ أيام</option>
                                <option value="7">٧ أيام</option>
                                <option value="30">٣٠ يومًا</option>
                            </select>
                            @error('duration') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reason">سبب الإيقاف</label>
                            <textarea id="reason" name="reason" rows="3" maxlength="255" class="form-control" placeholder="يظهر هذا السبب للمستخدم عند محاولته تسجيل الدخول."></textarea>
                            @error('reason') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <button class="btn btn-liqa-danger w-100">إيقاف الحساب</button>
                    </form>
                @endif

                @if ($canChangeRole)
                    <hr class="border-secondary">
                    <form method="POST" action="{{ route('admin.users.role', $u) }}"
                          onsubmit="return confirm('{{ $u->is_admin ? 'سحب صلاحية الإدارة من هذا المستخدم؟' : 'منح هذا المستخدم صلاحية الإدارة؟' }}')">
                        @csrf @method('PATCH')
                        <button class="btn btn-liqa-outline w-100">{{ $u->is_admin ? 'سحب صلاحية الإدارة' : 'جعله مديرًا' }}</button>
                    </form>
                @endif
            </div>
            @elseif ($u->isOwner())
                <div class="liqa-card p-3 p-md-4 mb-3"><p class="small text-secondary mb-0">المالك حساب محمي ولا يمكن إيقافه أو تغيير صلاحيته.</p></div>
            @endif

            @if ($me->isOwner() && $u->is_admin && ! $u->isOwner())
            <div class="liqa-card p-3 p-md-4 mb-3">
                <h2 class="h5 liqa-title mb-1">صلاحيات الأقسام</h2>
                <p class="small text-secondary">اختر الأقسام التي يستطيع هذا المدير الوصول إليها.</p>
                <form method="POST" action="{{ route('admin.users.permissions', $u) }}">
                    @csrf @method('PATCH')
                    <div class="perm-grid mb-3">
                        @foreach (\App\Models\User::PERMISSIONS as $key => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" id="perm-{{ $key }}" @checked($u->hasPermission($key))>
                                <label class="form-check-label" for="perm-{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    <button class="btn btn-liqa-outline w-100">حفظ الصلاحيات</button>
                </form>
            </div>
            @endif

            <div class="liqa-card p-3 p-md-4 mb-3">
                <h2 class="h5 liqa-title mb-3">بلاغات ضد هذا المستخدم</h2>
                @forelse ($reportsAgainst as $r)
                    <a href="{{ route('admin.reports') }}" class="request-row mb-2 d-block text-decoration-none">
                        <span class="liqa-badge liqa-badge--pink">{{ \App\Models\Report::REASONS[$r->reason] ?? $r->reason }}</span>
                        <span class="small text-secondary ms-2">{{ $r->reporter?->name ?? '—' }} · {{ $r->created_at->diffForHumans() }}</span>
                    </a>
                @empty
                    <p class="text-secondary small mb-0">لا توجد بلاغات.</p>
                @endforelse
            </div>

            <div class="liqa-card p-3 p-md-4">
                <h2 class="h5 liqa-title mb-3">إجراءات الإدارة على الحساب</h2>
                @forelse ($logs as $log)
                    <div class="mb-2">
                        <span class="liqa-badge {{ $log->actionClass() }}">{{ $log->actionLabel() }}</span>
                        <span class="small text-secondary ms-2">{{ $log->admin_name ?: '—' }} · {{ $log->created_at->diffForHumans() }}</span>
                        @if ($log->details) <div class="small text-secondary mt-1">{{ $log->details }}</div> @endif
                    </div>
                @empty
                    <p class="text-secondary small mb-0">لا توجد إجراءات مسجّلة.</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-7 order-lg-1">
            <div class="liqa-card p-3 p-md-4 mb-3">
                <h2 class="h5 liqa-title mb-3">الأحداث التي نظّمها</h2>
                @forelse ($hosted as $e)
                    <a href="{{ route('events.show', $e) }}" class="request-row mb-2 d-block text-decoration-none">
                        <span class="d-block fw-bold text-white">{{ $e->title }}</span>
                        <span class="d-block small text-secondary">{{ $e->whenLabel() }} · {{ $e->accepted_requests_count }}/{{ $e->capacity }}@if ($e->status === \App\Models\Event::CANCELLED) · ملغى @endif</span>
                    </a>
                @empty
                    <p class="text-secondary small mb-0">لم ينظّم أي حدث بعد.</p>
                @endforelse
            </div>

            <div class="liqa-card p-3 p-md-4 mb-3">
                <h2 class="h5 liqa-title mb-3">الأحداث التي شارك فيها</h2>
                @forelse ($joined as $r)
                    <a href="{{ route('events.show', $r->event) }}" class="request-row mb-2 d-block text-decoration-none">
                        <span class="d-block fw-bold text-white">{{ $r->event->title }}</span>
                        <span class="d-block small text-secondary">{{ $r->event->whenLabel() }} · المنظّم: {{ $r->event->host->name }}</span>
                    </a>
                @empty
                    <p class="text-secondary small mb-0">لم يشارك في أي حدث بعد.</p>
                @endforelse
            </div>

            <div class="liqa-card p-3 p-md-4">
                <h2 class="h5 liqa-title mb-3">التقييمات المستلمة</h2>
                @forelse ($ratings as $rt)
                    <div class="request-row mb-2 d-block">
                        <span class="fw-bold text-white">{{ str_repeat('★', $rt->stars) }}<span class="text-secondary">{{ str_repeat('★', 5 - $rt->stars) }}</span></span>
                        <span class="small text-secondary ms-2">من {{ $rt->rater?->name ?? '—' }} · {{ $rt->event?->title }}</span>
                        @if ($rt->comment) <div class="small mt-1">{{ $rt->comment }}</div> @endif
                    </div>
                @empty
                    <p class="text-secondary small mb-0">لا توجد تقييمات بعد.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
