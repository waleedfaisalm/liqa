@extends('layouts.admin')

@section('title', 'إدارة المستخدمين')

@section('content')
    @if (auth()->user()->hasPermission('export'))
    <div class="mb-3"><a href="{{ route('admin.export', 'users') }}" class="btn btn-sm btn-liqa-outline px-3">تصدير CSV</a></div>
    @endif
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h2 liqa-title mb-1">المستخدمون</h1>
            <p class="text-secondary mb-0">ابحث عن مستخدم، وأوقف حسابه أو أعد تفعيله، أو امنحه صلاحية الإدارة.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-liqa px-4 d-flex align-items-center gap-2">
            <x-icon name="plus" :size="18" /> إضافة مستخدم
        </a>
    </div>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="search" name="q" value="{{ $search }}" class="form-control" style="max-width: 360px; flex: 1 1 14rem" placeholder="ابحث بالاسم أو البريد الإلكتروني">
        <button class="btn btn-liqa px-4" type="submit">بحث</button>
    </form>

    <div class="tab-pills mb-3">
        @foreach (['all' => 'الكل', 'online' => 'متصلون الآن', 'suspended' => 'موقوفون', 'admins' => 'المديرون', 'unverified' => 'غير موثّقين'] as $key => $label)
            <a href="{{ route('admin.users', ['tab' => $key, 'q' => $search ?: null]) }}" class="tab-pill {{ $tab === $key ? 'is-active' : '' }}">{{ $label }} <span class="count">{{ $counts[$key] }}</span></a>
        @endforeach
    </div>

    <div class="liqa-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>التخصص</th>
                        <th>الأحداث</th>
                        <th>الانضمام</th>
                        <th>آخر ظهور</th>
                        <th>الحالة</th>
                        <th class="text-end">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td data-label="المستخدم">
                                <a href="{{ route('admin.users.show', $u) }}" class="d-flex align-items-center gap-2 text-decoration-none">
                                    <span class="avatar-wrap">
                                        <x-avatar :user="$u" :size="36" />
                                        @if ($u->isOnline()) <span class="presence-dot" title="متصل الآن"></span> @endif
                                    </span>
                                    <span>
                                        <span class="d-block fw-bold text-white">{{ $u->name }}</span>
                                        <span class="d-block small text-secondary" dir="ltr">{{ $u->email }}</span>
                                    </span>
                                </a>
                            </td>
                            <td data-label="التخصص">{{ $u->specialty ?: '—' }}</td>
                            <td data-label="الأحداث">{{ $u->hosted_events_count }}</td>
                            <td data-label="الانضمام" class="small text-secondary">{{ $u->created_at->translatedFormat('j F Y') }}</td>
                            <td data-label="آخر ظهور" class="small">
                                @if ($u->isOnline())
                                    <span class="text-success-emphasis fw-bold"><span class="presence-dot presence-dot--inline"></span> متصل الآن</span>
                                @elseif ($u->last_seen_at)
                                    <span class="text-secondary">{{ $u->last_seen_at->diffForHumans() }}</span>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td data-label="الحالة">
                                <div class="d-flex flex-wrap gap-1">
                                    @if ($u->isOwner()) <span class="liqa-badge liqa-badge--sky">المالك</span> @elseif ($u->is_admin) <span class="liqa-badge">مدير</span> @endif
                                    @if ($u->isSuspended()) <span class="liqa-badge liqa-badge--red">موقوف</span> @endif
                                    @unless ($u->hasVerifiedEmail()) <span class="liqa-badge liqa-badge--pink">غير موثّق</span> @endunless
                                    @if (! $u->is_admin && ! $u->isSuspended() && $u->hasVerifiedEmail()) <span class="liqa-badge liqa-badge--green">نشط</span> @endif
                                </div>
                            </td>
                            <td data-label="الإجراءات">
                                @php
                                    $me = auth()->user();
                                    $canSuspend = ! $u->is($me) && ! $u->isOwner() && ($me->isOwner() || ! $u->is_admin);
                                    $canChangeRole = $me->isOwner() && ! $u->is($me) && ! $u->isOwner();
                                @endphp
                                @if ($canSuspend || $canChangeRole)
                                    <div class="d-flex flex-wrap gap-1 justify-content-end">
                                        @if ($canSuspend)
                                        @if ($u->isSuspended())
                                        <form method="POST" action="{{ route('admin.users.suspend', $u) }}" onsubmit="return confirm('إعادة تفعيل هذا الحساب؟')">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-liqa px-3">إعادة تفعيل</button>
                                        </form>
                                        @else
                                        <a href="{{ route('admin.users.show', $u) }}#suspend" class="btn btn-sm btn-liqa-danger px-3">إيقاف</a>
                                        @endif
                                        @endif
                                        @if ($canChangeRole)
                                        <form method="POST" action="{{ route('admin.users.role', $u) }}"
                                              onsubmit="return confirm('{{ $u->is_admin ? 'سحب صلاحية الإدارة من هذا المستخدم؟' : 'منح هذا المستخدم صلاحية الإدارة؟' }}')">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-liqa-outline px-3">{{ $u->is_admin ? 'سحب الإدارة' : 'جعله مديرًا' }}</button>
                                        </form>
                                        @endif
                                    </div>
                                @elseif ($u->is($me))
                                    <span class="small text-secondary">حسابك</span>
                                @elseif ($u->isOwner())
                                    <span class="small text-secondary">المالك — حساب محمي</span>
                                @else
                                    <span class="small text-secondary">من صلاحية المالك</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">لا توجد نتائج.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
