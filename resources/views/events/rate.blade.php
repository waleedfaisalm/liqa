@extends('layouts.app')
@section('title', __('تقييم ').$ratee->name)
@section('content')
    <div class="mx-auto" style="max-width: 640px">
        <div class="form-card">
            <div class="text-center">
                <span class="eyebrow mb-3"><span class="dot"></span>{{ __('تقييم الحدث') }}</span>
                <h1 class="h2 liqa-title mb-3">{{ __('قيّم تجربتك مع') }}</h1>

                <div class="d-inline-flex align-items-center gap-3 mb-2">
                    <x-avatar :user="$ratee" :size="56" />
                    <div class="text-start">
                        <div class="fw-bold fs-5 text-white">{{ $ratee->name }}</div>
                        <div class="small text-secondary">{{ $ratee->id === $event->user_id ? __('المنظّم') : $ratee->specialty }}</div>
                    </div>
                </div>

                <div class="small hint mb-4 d-flex align-items-center justify-content-center gap-2">
                    <x-icon name="calendar" :size="15" /> {{ $event->title }} · {{ $event->starts_at->translatedFormat('j F Y') }}
                </div>
            </div>

            <form method="POST" action="{{ route('events.rate', [$event, $ratee]) }}" novalidate class="d-grid gap-4">
                @csrf

                <div class="text-center">
                    <div class="liqa-label">{{ __('كم نجمة تمنح؟') }} <span class="req">*</span></div>
                    <div class="star-input star-input--lg" role="radiogroup" aria-label="{{ __('التقييم من 1 إلى 5') }}">
                        @for ($i = 5; $i >= 1; $i--)
                            <input type="radio" name="stars" id="star-{{ $i }}" value="{{ $i }}" @checked((int) old('stars') === $i)>
                            <label for="star-{{ $i }}" title="{{ __(':v من 5', ['v' => $i]) }}">★</label>
                        @endfor
                    </div>
                    @error('stars') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <div class="liqa-label">{{ __('ماذا تصف هذا الشخص؟') }} <span class="small text-secondary fw-normal">{{ __('(اختياري، بحد أقصى 5)') }}</span></div>
                    <div class="d-flex flex-wrap gap-2" id="tag-list">
                        @foreach (\App\Models\Rating::TAGS as $i => $tag)
                            <div>
                                <input type="checkbox" class="btn-check js-tag" name="tags[]" id="tag-{{ $i }}" value="{{ $tag }}" @checked(in_array($tag, old('tags', [])))>
                                <label class="liqa-chip" for="tag-{{ $i }}">{{ __($tag) }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('tags') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="comment">{{ __('تعليق') }} <span class="small text-secondary fw-normal">{{ __('(اختياري)') }}</span></label>
                    <textarea id="comment" name="comment" rows="3" maxlength="300"
                              class="form-control @error('comment') is-invalid @enderror"
                              placeholder="{{ __('ما أبرز ما لاحظته في اللقاء؟ اكتب ملاحظاتك بإيجاز ومهنية.') }}">{{ old('comment') }}</textarea>
                    @error('comment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-liqa flex-grow-1 d-flex align-items-center justify-content-center gap-2">
                        {{ __('إرسال التقييم') }} <x-icon name="send" :size="18" />
                    </button>
                    <a href="{{ route('events.show', $event) }}#ratings" class="btn btn-liqa-outline px-4 d-flex align-items-center">{{ __('إلغاء') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // الحد الأقصى 5 صفات
    var tags = document.querySelectorAll('.js-tag');
    function limitTags() {
        var checked = document.querySelectorAll('.js-tag:checked').length;
        tags.forEach(function (t) { if (!t.checked) t.disabled = checked >= 5; });
    }
    tags.forEach(function (t) { t.addEventListener('change', limitTags); });
    limitTags();
</script>
@endpush
