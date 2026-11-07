@extends('layouts.admin')

@section('title', 'رسائل التواصل')

@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">رسائل التواصل</h1>
        <p class="text-secondary mb-0">الرسائل الواردة من نموذج «تواصل معنا». للرد، اضغط على بريد المرسل.</p>
    </div>

    <div class="tab-pills mb-3">
        <a href="{{ route('admin.messages') }}" class="tab-pill {{ $tab === 'all' ? 'is-active' : '' }}">الكل <span class="count">{{ $total }}</span></a>
        <a href="{{ route('admin.messages', ['tab' => 'unread']) }}" class="tab-pill {{ $tab === 'unread' ? 'is-active' : '' }}">غير مقروءة <span class="count">{{ $unread }}</span></a>
    </div>

    @forelse ($messages as $m)
        <div class="liqa-card p-3 p-md-4 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="liqa-badge {{ $m->isUnread() ? 'liqa-badge--pink' : '' }}">{{ $m->subject }}</span>
                    <span class="fw-bold text-white ms-2">{{ $m->name }}</span>
                    <a href="mailto:{{ $m->email }}?subject={{ rawurlencode('رد: '.$m->subject) }}" class="small ms-2" dir="ltr">{{ $m->email }}</a>
                    @if ($m->user) <a href="{{ route('admin.users.show', $m->user) }}" class="small ms-2">عضو في المنصة</a> @endif
                </div>
                <span class="small text-secondary">{{ $m->created_at->diffForHumans() }}</span>
            </div>
            <p class="mb-3" style="white-space: pre-line">{{ $m->body }}</p>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.messages.toggle', $m) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-liqa-outline px-3">{{ $m->isUnread() ? 'تعليم كمقروءة' : 'تعليم كغير مقروءة' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.messages.destroy', $m) }}" onsubmit="return confirm('حذف هذه الرسالة نهائيًا؟')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-liqa-danger px-3">حذف</button>
                </form>
            </div>
        </div>
    @empty
        <div class="liqa-card p-4 text-center text-secondary">لا توجد رسائل.</div>
    @endforelse

    <div class="mt-4">{{ $messages->links() }}</div>
@endsection
