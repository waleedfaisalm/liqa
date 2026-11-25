@extends('layouts.app')
@section('title', __('التعديل والإعدادات'))
@section('content')
    @php $user = auth()->user(); @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <h1 class="h2 liqa-title mb-0">{{ __('التعديل والإعدادات') }}</h1>
        <a href="{{ route('profile') }}" class="btn btn-liqa-outline px-3 d-flex align-items-center">{{ __('العودة إلى الملف الشخصي') }}</a>
    </div>

    <div class="row g-4">
        {{-- بيانات البروفايل --}}
        <div class="col-lg-7">
            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" novalidate class="liqa-card d-grid gap-3">
                @csrf
                @method('PUT')

                <h2 class="h5 liqa-title mb-0">{{ __('بيانات الملف الشخصي') }}</h2>

                <div class="d-flex align-items-center gap-3">
                    <x-avatar :user="$user" :size="76" />
                    <div class="flex-grow-1">
                        <label class="liqa-label d-block" for="avatar">{{ __('تغيير الصورة') }}</label>
                        <input id="avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp"
                               class="form-control form-control-sm @error('avatar') is-invalid @enderror">
                        @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($user->avatar_path)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_avatar" id="remove_avatar" value="1">
                                <label class="form-check-label small" for="remove_avatar">{{ __('حذف الصورة الحالية') }}</label>
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="liqa-label d-block" for="name">{{ __('الاسم') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="specialty">{{ __('التخصص') }}</label>
                    <select id="specialty" name="specialty" class="form-select @error('specialty') is-invalid @enderror">
                        @foreach (collect(\App\Support\Catalog::specialties())->when($user->specialty, fn ($c) => $c->push($user->specialty)->unique())->values() as $specialty)
                            <option value="{{ $specialty }}" @selected(old('specialty', $user->specialty) === $specialty)>{{ __($specialty) }}</option>
                        @endforeach
                    </select>
                    @error('specialty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="city">{{ __('مدينتك (اختياري)') }}</label>
                    <input type="text" id="city" name="city" maxlength="60" list="city-list" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $user->city) }}" placeholder="{{ __('مثال: عدن') }}">
                    <datalist id="city-list">
                        @foreach (\App\Models\Event::whereNotNull('city')->distinct()->pluck('city') as $c) <option value="{{ $c }}"></option> @endforeach
                    </datalist>
                    <div class="small hint mt-1">{{ __('تساعدنا في تنظيم لقاءات في مدينتك. لا تظهر إلا كمدينة عامة في ملفك.') }}</div>
                    @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="bio">{{ __('نبذة عنك') }}</label>
                    <textarea id="bio" name="bio" rows="4" maxlength="300"
                              class="form-control @error('bio') is-invalid @enderror">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <div class="liqa-label d-block mb-2">{{ __('روابط أعمالك') }}</div>
                    <div class="row g-2">
                        @foreach (['link_linkedin' => ['LinkedIn', 'linkedin.com/in/username'], 'link_github' => ['GitHub', 'github.com/username'], 'link_behance' => ['Behance', 'behance.net/username'], 'link_website' => [__('الموقع الشخصي'), 'example.com']] as $field => [$label, $ph])
                            <div class="col-md-6">
                                <label class="small text-secondary d-block mb-1" for="{{ $field }}">{{ $label }}</label>
                                <input type="text" dir="ltr" id="{{ $field }}" name="{{ $field }}" maxlength="200" placeholder="{{ $ph }}"
                                       value="{{ old($field, $user->{$field}) }}" class="form-control @error($field) is-invalid @enderror">
                                @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text small hint">{{ __('اختيارية، وتظهر في ملفك الشخصي لمن يتصفح ملفك.') }}</div>
                </div>

                <div class="form-check form-switch">
                    <input type="hidden" name="notifications_enabled" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="notifications_enabled" id="notifications_enabled"
                           value="1" @checked(old('notifications_enabled', $user->notifications_enabled))>
                    <label class="form-check-label" for="notifications_enabled">{{ __('إشعارات التطبيق') }}</label>
                </div>

                <div class="form-check form-switch">
                    <input type="hidden" name="profile_public" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="profile_public" id="profile_public"
                           value="1" @checked(old('profile_public', $user->profile_public))>
                    <label class="form-check-label" for="profile_public">{{ __('إظهار ملفي في دليل الأعضاء') }}</label>
                    <div class="hint">{{ __('عند الإيقاف لا يظهر اسمك في صفحة «الأعضاء» والبحث، ويبقى ملفك ظاهرًا لمن يصل إليه من الأحداث.') }}</div>
                </div>

                <button type="submit" class="btn btn-liqa">{{ __('حفظ التغييرات') }}</button>
            </form>
        </div>

        {{-- كلمة السر + الخروج --}}
        <div class="col-lg-5">
            <form method="POST" action="{{ route('settings.password') }}" novalidate class="liqa-card d-grid gap-3 mb-3">
                @csrf
                @method('PUT')

                <h2 class="h5 liqa-title mb-0">{{ __('تغيير كلمة السر') }}</h2>

                <div>
                    <label class="liqa-label d-block" for="current_password">{{ __('كلمة السر الحالية') }}</label>
                    <input id="current_password" type="password" name="current_password" autocomplete="current-password"
                           class="form-control @error('current_password', 'password') is-invalid @enderror">
                    @error('current_password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="password">{{ __('كلمة السر الجديدة') }}</label>
                    <input id="password" type="password" name="password" autocomplete="new-password"
                           class="form-control @error('password', 'password') is-invalid @enderror">
                    @error('password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="password_confirmation">{{ __('تأكيد كلمة السر') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="form-control">
                </div>

                <button type="submit" class="btn btn-liqa-outline">{{ __('تغيير كلمة السر') }}</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-liqa-danger w-100">{{ __('تسجيل خروج') }}</button>
            </form>

            @unless (auth()->user()->is_admin)
            <form method="POST" action="{{ route('settings.destroy') }}" novalidate class="liqa-card d-grid gap-3 mt-3"
                  onsubmit="return confirm('{{ addslashes(__('سيُحذف حسابك وكل بياناته نهائيًا ولا يمكن التراجع. هل أنت متأكد؟')) }}')">
                @csrf
                @method('DELETE')
                <h2 class="h5 liqa-title mb-0 text-danger">{{ __('حذف الحساب') }}</h2>
                <p class="small text-secondary mb-0">{{ __('يحذف حسابك وأحداثك وتقييماتك وتعليقاتك نهائيًا. وسيُبلَّغ المشاركون في أحداثك القادمة بإلغائها.') }}</p>
                <div>
                    <label class="liqa-label d-block" for="delete_password">{{ __('كلمة السر للتأكيد') }}</label>
                    <input id="delete_password" type="password" name="delete_password" autocomplete="current-password"
                           class="form-control @error('delete_password', 'delete') is-invalid @enderror">
                    @error('delete_password', 'delete') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-liqa-danger">{{ __('حذف حسابي نهائيًا') }}</button>
            </form>
            @endunless
        </div>
    </div>
@endsection
