@extends(auth()->check() ? 'layouts.app' : 'layouts.site')
@section('title', __('تواصل معنا'))
@section('content')
    <div class="mx-auto" style="max-width: 620px">
        <h1 class="h2 liqa-title mb-2">{{ __('تواصل معنا') }}</h1>
        <p class="text-secondary mb-4">{{ __('لديك استفسار أو مشكلة أو اقتراح؟ اكتب لنا وسنرد على بريدك الإلكتروني.') }}</p>

        <form method="POST" action="{{ route('contact.store') }}" novalidate class="liqa-card d-grid gap-3">
            @csrf
            <div style="position:absolute;left:-9999px" aria-hidden="true">
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label class="liqa-label d-block" for="name">{{ __('الاسم') }}</label>
                <input id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" maxlength="80" required class="form-control @error('name') is-invalid @enderror">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="liqa-label d-block" for="email">{{ __('البريد الإلكتروني') }}</label>
                <input id="email" type="email" name="email" dir="ltr" value="{{ old('email', auth()->user()?->email) }}" required class="form-control text-end @error('email') is-invalid @enderror">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="liqa-label d-block" for="subject">{{ __('الموضوع') }}</label>
                <select id="subject" name="subject" class="form-select @error('subject') is-invalid @enderror" required>
                    <option value="">{{ __('اختر الموضوع') }}</option>
                    @foreach ($subjects as $key => $label)
                        <option value="{{ $key }}" @selected(old('subject') === $key)>{{ __($label) }}</option>
                    @endforeach
                </select>
                @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="liqa-label d-block" for="body">{{ __('الرسالة') }}</label>
                <textarea id="body" name="body" rows="6" maxlength="2000" required class="form-control @error('body') is-invalid @enderror">{{ old('body') }}</textarea>
                @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-liqa py-2">{{ __('إرسال الرسالة') }}</button>
        </form>
    </div>
@endsection
