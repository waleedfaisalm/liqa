@extends('layouts.app')

@section('title', 'التعديل والإعدادات')

@section('content')
    @php $user = auth()->user(); @endphp

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h2 liqa-title mb-0">التعديل والإعدادات</h1>
        <a href="{{ route('profile') }}" class="btn btn-liqa-outline px-3 d-flex align-items-center">العودة إلى الملف الشخصي</a>
    </div>

    <div class="row g-4">
        {{-- بيانات البروفايل --}}
        <div class="col-lg-7">
            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" novalidate class="liqa-card d-grid gap-3">
                @csrf
                @method('PUT')

                <h2 class="h5 liqa-title mb-0">بيانات الملف الشخصي</h2>

                <div class="d-flex align-items-center gap-3">
                    <x-avatar :user="$user" :size="76" />
                    <div class="flex-grow-1">
                        <label class="liqa-label d-block" for="avatar">تغيير الصورة</label>
                        <input id="avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp"
                               class="form-control form-control-sm @error('avatar') is-invalid @enderror">
                        @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($user->avatar_path)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_avatar" id="remove_avatar" value="1">
                                <label class="form-check-label small" for="remove_avatar">حذف الصورة الحالية</label>
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="liqa-label d-block" for="name">الاسم</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="specialty">التخصص</label>
                    <select id="specialty" name="specialty" class="form-select @error('specialty') is-invalid @enderror">
                        @foreach (collect(\App\Support\Catalog::specialties())->when($user->specialty, fn ($c) => $c->push($user->specialty)->unique())->values() as $specialty)
                            <option value="{{ $specialty }}" @selected(old('specialty', $user->specialty) === $specialty)>{{ $specialty }}</option>
                        @endforeach
                    </select>
                    @error('specialty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="bio">نبذة عنك</label>
                    <textarea id="bio" name="bio" rows="4" maxlength="300"
                              class="form-control @error('bio') is-invalid @enderror">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-check form-switch">
                    <input type="hidden" name="notifications_enabled" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="notifications_enabled" id="notifications_enabled"
                           value="1" @checked(old('notifications_enabled', $user->notifications_enabled))>
                    <label class="form-check-label" for="notifications_enabled">إشعارات التطبيق</label>
                </div>

                <button type="submit" class="btn btn-liqa">حفظ التغييرات</button>
            </form>
        </div>

        {{-- كلمة السر + الخروج --}}
        <div class="col-lg-5">
            <form method="POST" action="{{ route('settings.password') }}" novalidate class="liqa-card d-grid gap-3 mb-3">
                @csrf
                @method('PUT')

                <h2 class="h5 liqa-title mb-0">تغيير كلمة السر</h2>

                <div>
                    <label class="liqa-label d-block" for="current_password">كلمة السر الحالية</label>
                    <input id="current_password" type="password" name="current_password" autocomplete="current-password"
                           class="form-control @error('current_password', 'password') is-invalid @enderror">
                    @error('current_password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="password">كلمة السر الجديدة</label>
                    <input id="password" type="password" name="password" autocomplete="new-password"
                           class="form-control @error('password', 'password') is-invalid @enderror">
                    @error('password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="password_confirmation">تأكيد كلمة السر</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="form-control">
                </div>

                <button type="submit" class="btn btn-liqa-outline">تغيير كلمة السر</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-liqa-danger w-100">تسجيل خروج</button>
            </form>

            @unless (auth()->user()->is_admin)
            <form method="POST" action="{{ route('settings.destroy') }}" novalidate class="liqa-card d-grid gap-3 mt-3"
                  onsubmit="return confirm('سيُحذف حسابك وكل بياناته نهائيًا ولا يمكن التراجع. هل أنت متأكد؟')">
                @csrf
                @method('DELETE')
                <h2 class="h5 liqa-title mb-0 text-danger">حذف الحساب</h2>
                <p class="small text-secondary mb-0">يحذف حسابك وأحداثك وتقييماتك وتعليقاتك نهائيًا. وسيُبلَّغ المشاركون في أحداثك القادمة بإلغائها.</p>
                <div>
                    <label class="liqa-label d-block" for="delete_password">كلمة السر للتأكيد</label>
                    <input id="delete_password" type="password" name="delete_password" autocomplete="current-password"
                           class="form-control @error('delete_password', 'delete') is-invalid @enderror">
                    @error('delete_password', 'delete') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-liqa-danger">حذف حسابي نهائيًا</button>
            </form>
            @endunless
        </div>
    </div>
@endsection
