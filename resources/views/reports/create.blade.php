@extends('layouts.app')
@section('title', __('إرسال بلاغ'))
@section('content')
    @php $isUser = $type === 'user'; @endphp
    <div class="mx-auto" style="max-width: 640px">
        <div class="form-card">
            <span class="eyebrow eyebrow--pink mb-3"><span class="dot"></span>{{ __('إرسال بلاغ') }}</span>
            <h1 class="h2 liqa-title mb-3">{{ $isUser ? __('إبلاغ عن مستخدم') : __('إبلاغ عن حدث') }}</h1>

            <div class="request-row mb-4">
                @if ($isUser)
                    <x-avatar :user="$target" :size="44" />
                    <span>
                        <span class="d-block fw-bold text-white">{{ $target->name }}</span>
                        <span class="d-block small text-secondary">{{ __($target->specialty) }}</span>
                    </span>
                @else
                    <span class="info-tile__icon"><x-icon name="calendar" :size="20" /></span>
                    <span>
                        <span class="d-block fw-bold text-white">{{ $target->title }}</span>
                        <span class="d-block small text-secondary">{{ __(':v1 · المنظّم: :v2', ['v1' => $target->whenLabel(), 'v2' => $target->host->name]) }}</span>
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('reports.store') }}" novalidate class="d-grid gap-4">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="id" value="{{ $target->id }}">

                <div>
                    <div class="liqa-label">{{ __('سبب البلاغ') }} <span class="req">*</span></div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (\App\Models\Report::REASONS as $key => $label)
                            <div>
                                <input type="radio" class="btn-check" name="reason" id="reason-{{ $key }}" value="{{ $key }}" @checked(old('reason') === $key)>
                                <label class="liqa-chip" for="reason-{{ $key }}">{{ __($label) }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('reason') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="details">{{ __('التفاصيل') }} <span class="small text-secondary fw-normal">{{ __('(اختياري، ويلزم عند اختيار "سبب آخر")') }}</span></label>
                    <textarea id="details" name="details" rows="4" maxlength="500"
                              class="form-control @error('details') is-invalid @enderror"
                              placeholder="{{ __('اشرح ما حدث بإيجاز ووضوح.') }}">{{ old('details') }}</textarea>
                    @error('details') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <p class="small hint mb-0">{{ __('سيطّلع فريق المنصة على بلاغك، ولن يُكشف اسمك للطرف الآخر.') }}</p>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-liqa px-4">{{ __('إرسال البلاغ') }}</button>
                    <a href="{{ $isUser ? route('users.show', $target) : route('events.show', $target) }}" class="btn btn-liqa-outline px-4 d-flex align-items-center">{{ __('إلغاء') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
