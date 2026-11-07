@extends(auth()->check() ? 'layouts.app' : 'layouts.site')

@section('title', 'الأحداث')

@section('content')
<div @guest class="container py-4 py-lg-5" @endguest>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 liqa-title mb-1">الأحداث الجاية</h1>
            <p class="text-secondary mb-0">لقاءات للفريلانسرز، حضورية وعن بُعد. قدّم طلب انضمام وانتظر موافقة المنظّم.</p>
        </div>
        @auth
            <a href="{{ route('events.create') }}" class="btn btn-liqa px-4">+ انشر حدث</a>
        @else
            <a href="{{ route('register') }}" class="btn btn-liqa px-4">أنشئ حسابك للانضمام</a>
        @endauth
    </div>

    <form method="GET" action="{{ route('events.index') }}" class="liqa-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="liqa-label d-block" for="q">بحث</label>
                <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="عنوان الحدث أو المدينة أو المكان">
            </div>
            <div class="col-6 col-md-2">
                <label class="liqa-label d-block" for="country">الدولة</label>
                <select id="country" name="country" class="form-select">
                    <option value="">الكل</option>
                    @foreach (array_keys(\App\Support\Catalog::places()) as $countryName)
                        <option value="{{ $countryName }}" @selected($country === $countryName)>{{ $countryName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="liqa-label d-block" for="type">التصنيف</label>
                <select id="type" name="type" class="form-select">
                    <option value="">الكل</option>
                    @foreach (\App\Support\Catalog::eventTypes() as $key => $label)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="liqa-label d-block" for="mode">الطريقة</label>
                <select id="mode" name="mode" class="form-select">
                    <option value="">الكل</option>
                    @foreach (\App\Models\Event::MODES as $key => $label)
                        <option value="{{ $key }}" @selected($mode === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-liqa" type="submit">بحث</button>
            </div>
        </div>
    </form>

    @if ($events->isEmpty())
        <div class="liqa-card text-center py-5">
            <p class="fw-bold fs-5 mb-1">لا توجد أحداث مطابقة</p>
            <p class="text-secondary mb-4">كن أول من ينشر حدثًا في مجالك.</p>
            @auth <a href="{{ route('events.create') }}" class="btn btn-liqa px-4">انشر حدث</a> @endauth
        </div>
    @else
        <div class="row g-3">
            @foreach ($events as $event)
                <div class="col-md-6 col-lg-4"><x-event-card :event="$event" :user="auth()->user()" /></div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">{{ $events->links() }}</div>
    @endif
</div>
@endsection
