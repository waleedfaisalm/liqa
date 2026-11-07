@extends('layouts.admin')

@section('title', 'إضافة مستخدم')

@section('content')
    <div class="mx-auto" style="max-width: 640px">
        <div class="form-card">
            <span class="eyebrow eyebrow--pink mb-3"><span class="dot"></span>مستخدم جديد</span>
            <h1 class="h2 liqa-title mb-1">إضافة مستخدم</h1>
            <p class="hint mb-4">أنشئ حسابًا وحدّد صلاحيته. يُعتبر بريده موثّقًا، ويمكنه الدخول مباشرة.</p>

            <form method="POST" action="{{ route('admin.users.store') }}" novalidate class="d-grid gap-4">
                @csrf

                <div>
                    <label class="liqa-label d-block" for="name">الاسم <span class="req">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" maxlength="80" required
                           class="form-control @error('name') is-invalid @enderror" placeholder="الاسم الكامل">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="email">البريد الإلكتروني <span class="req">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required dir="ltr"
                           class="form-control text-end @error('email') is-invalid @enderror" placeholder="name@email.com">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="password">كلمة السر <span class="req">*</span></label>
                    <div class="input-group">
                        <input id="password" type="text" name="password" value="{{ old('password') }}" required dir="ltr" autocomplete="new-password"
                               class="form-control text-end @error('password') is-invalid @enderror" placeholder="8 أحرف على الأقل">
                        <button type="button" id="gen-password" class="btn btn-liqa-outline">توليد</button>
                    </div>
                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div class="hint mt-1">سلّم كلمة السر للمستخدم بطريقة آمنة، وينصح بتغييرها بعد أول دخول.</div>
                </div>

                <div>
                    <label class="liqa-label d-block" for="specialty">التخصص <span class="small text-secondary fw-normal">(اختياري)</span></label>
                    <select id="specialty" name="specialty" class="form-select @error('specialty') is-invalid @enderror">
                        <option value="">بدون تحديد</option>
                        @foreach (\App\Support\Catalog::specialties() as $s)
                            <option value="{{ $s }}" @selected(old('specialty') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('specialty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <div class="liqa-label">الصلاحية <span class="req">*</span></div>
                    <div class="d-flex flex-wrap gap-2">
                        <div>
                            <input type="radio" class="btn-check" name="role" id="role-member" value="member" @checked(old('role', 'member') === 'member')>
                            <label class="liqa-chip" for="role-member">عضو عادي</label>
                        </div>
                        @if (auth()->user()->isOwner())
                            <div>
                                <input type="radio" class="btn-check" name="role" id="role-admin" value="admin" @checked(old('role') === 'admin')>
                                <label class="liqa-chip" for="role-admin">مدير (يدخل لوحة الإدارة)</label>
                            </div>
                        @endif
                    </div>
                    @unless (auth()->user()->isOwner())
                        <div class="hint mt-2">إنشاء حسابات المديرين من صلاحية المالك فقط.</div>
                    @endunless
                    @error('role') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-liqa px-4">إنشاء الحساب</button>
                    <a href="{{ route('admin.users') }}" class="btn btn-liqa-outline px-4 d-flex align-items-center">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('gen-password').addEventListener('click', function () {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        var out = '';
        var buf = new Uint32Array(12);
        window.crypto.getRandomValues(buf);
        buf.forEach(function (n) { out += chars[n % chars.length]; });
        document.getElementById('password').value = out;
    });
</script>
@endpush
