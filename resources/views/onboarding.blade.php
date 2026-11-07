@extends('layouts.guest')

@section('title', 'عرّفنا بنفسك')

@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-2">عرّفنا بنفسك</h1>
        <p class="text-secondary mb-0">سيطّلع الآخرون على ملفك الشخصي قبل قبولك في أي لقاء.</p>
    </div>

    <form method="POST" action="{{ route('onboarding.store') }}" enctype="multipart/form-data" novalidate class="d-grid gap-4">
        @csrf

        {{-- الصورة --}}
        <div class="d-flex align-items-center gap-3">
            <label for="avatar" class="avatar-drop" title="اختر صورة">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#C084FC" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8.5A2.5 2.5 0 016.5 6h1.2l1.3-2h6l1.3 2h1.2A2.5 2.5 0 0120 8.5v8A2.5 2.5 0 0117.5 19h-11A2.5 2.5 0 014 16.5z"/><circle cx="12" cy="12.5" r="3.2"/></svg>
            </label>
            <div>
                <div class="fw-bold">صورتك الشخصية <span class="small text-secondary fw-normal">(اختياري)</span></div>
                <input id="avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp"
                       class="form-control form-control-sm mt-2 @error('avatar') is-invalid @enderror">
                @error('avatar') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- التخصص --}}
        <div>
            <div class="liqa-label">تخصصك</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach (\App\Support\Catalog::specialties() as $i => $specialty)
                    <div>
                        <input type="radio" class="btn-check" name="specialty" id="sp{{ $i }}" value="{{ $specialty }}"
                               @checked(old('specialty') === $specialty)>
                        <label class="liqa-chip" for="sp{{ $i }}">{{ $specialty }}</label>
                    </div>
                @endforeach
            </div>
            @error('specialty') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
        </div>

        {{-- النبذة --}}
        <div>
            <label class="liqa-label d-block" for="bio">نبذة عنك <span class="small text-secondary fw-normal">(اختياري)</span></label>
            <textarea id="bio" name="bio" rows="4" maxlength="300"
                      class="form-control @error('bio') is-invalid @enderror"
                      placeholder="اكتب سطرين عن عملك وما تبحث عنه في اللقاءات">{{ old('bio') }}</textarea>
            @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-liqa">ابدأ الآن</button>
    </form>
@endsection
