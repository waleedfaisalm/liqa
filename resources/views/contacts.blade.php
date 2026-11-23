@extends('layouts.app')
@section('title', __('التواصل'))
@section('content')
<div class="mx-auto" style="max-width: 760px">
    <h1 class="h3 liqa-title mb-1">{{ __('شبكة التواصل') }}</h1>
    <p class="hint mb-4">{{ __('يمكنك طلب التواصل مع من حضرت معهم حدثًا فعليًا. عند القبول تظهر بيانات التواصل لكليكما.') }}</p>

    @if ($incoming->isNotEmpty())
        <div class="liqa-card p-3 p-md-4 mb-3">
            <h2 class="h5 liqa-title mb-3">{{ __('طلبات واردة (:n)', ['n' => $incoming->count()]) }}</h2>
            <div class="d-grid gap-2">
                @foreach ($incoming as $c)
                    <div class="request-row">
                        <a href="{{ route('users.show', $c->from) }}" class="d-flex align-items-center gap-2 text-decoration-none me-auto">
                            <x-avatar :user="$c->from" :size="40" />
                            <span>
                                <span class="d-block fw-bold text-white">{{ $c->from->name }}</span>
                                @if ($c->event) <span class="d-block small hint">{{ __('التقيتما في :t', ['t' => $c->event->title]) }}</span> @endif
                            </span>
                        </a>
                        <form method="POST" action="{{ route('contacts.decide', $c) }}" class="d-flex gap-2">
                            @csrf @method('PATCH')
                            <button name="decision" value="accept" class="btn btn-liqa btn-sm px-3" style="min-height:2.25rem">{{ __('قبول') }}</button>
                            <button name="decision" value="decline" class="btn btn-liqa-outline btn-sm px-3" style="min-height:2.25rem">{{ __('رفض') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
            <p class="small hint mt-2 mb-0">{{ __('بقبولك يطّلع الطرف الآخر على بريدك وروابط أعمالك.') }}</p>
        </div>
    @endif

    <div class="liqa-card p-3 p-md-4 mb-3">
        <h2 class="h5 liqa-title mb-3">{{ __('جهات اتصالي (:n)', ['n' => $connections->count()]) }}</h2>
        @forelse ($connections as $c)
            @php $u = $c['user']; @endphp
            <div class="request-row d-block mb-2">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <x-avatar :user="$u" :size="40" />
                    <a href="{{ route('users.show', $u) }}" class="fw-bold text-white text-decoration-none">{{ $u->name }}</a>
                    @if ($c['event']) <span class="small hint ms-auto">{{ $c['event']->title }}</span> @endif
                </div>
                <div class="d-flex flex-wrap gap-2 small">
                    <a href="mailto:{{ $u->email }}" class="liqa-badge text-decoration-none">{{ $u->email }}</a>
                    @foreach ($u->workLinks() as $l)
                        <a href="{{ $l[1] }}" target="_blank" rel="noopener nofollow ugc" class="liqa-badge text-decoration-none">{{ $l[0] }}</a>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-secondary mb-0">{{ __('لا توجد جهات اتصال بعد. بعد حضورك حدثًا ستجد زر «تواصل» بجانب الحاضرين.') }}</p>
        @endforelse
    </div>

    @if ($outgoing->isNotEmpty())
        <div class="liqa-card p-3 p-md-4">
            <h2 class="h6 liqa-title mb-3">{{ __('طلبات أرسلتها (بانتظار الرد)') }}</h2>
            <div class="d-grid gap-2">
                @foreach ($outgoing as $c)
                    <div class="request-row">
                        <a href="{{ route('users.show', $c->to) }}" class="fw-bold text-white text-decoration-none me-auto">{{ $c->to->name }}</a>
                        <form method="POST" action="{{ route('contacts.cancel', $c) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-liqa-outline btn-sm px-3">{{ __('إلغاء الطلب') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
