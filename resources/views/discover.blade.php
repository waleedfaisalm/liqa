@extends(auth()->check() ? 'layouts.app' : 'layouts.site')

@section('title', $heading)
@section('description', $count > 0
    ? 'اكتشف '.$count.' من اللقاءات القادمة للفريلانسرز '.$subject.'، وقدّم طلب انضمام بسهولة عبر منصة لقاء.'
    : 'لقاءات الفريلانسرز '.$subject.' على منصة لقاء. كن أول من ينشر لقاءً.')

@push('head')
    @if ($count === 0)<meta name="robots" content="noindex, follow">@endif
    @if ($events->currentPage() > 1)<meta name="robots" content="noindex, follow">@endif
@endpush

@section('content')
<div @guest class="container py-4 py-lg-5" @endguest>
    <nav aria-label="مسار التصفح" class="small mb-3">
        <a href="{{ route('events.index') }}" class="text-decoration-none">الأحداث</a>
        @foreach ($crumbs as [$label, $url])
            <span class="text-secondary"> / </span>
            @if ($url)<a href="{{ $url }}" class="text-decoration-none">{{ $label }}</a>@else<span class="text-secondary">{{ $label }}</span>@endif
        @endforeach
    </nav>

    <div class="mb-4">
        <h1 class="h2 liqa-title mb-2">{{ $heading }}</h1>
        <p class="text-secondary mb-0" style="max-width: 46rem">
            @if ($count > 0)
                {{ $count }} {{ $count === 1 ? 'لقاء قادم' : 'لقاءات قادمة' }} {{ $subject }}. تعرّف على فريلانسرز من تخصصات مختلفة، وقدّم طلب انضمام وانتظر موافقة المنظّم.
            @else
                لا توجد لقاءات قادمة {{ $subject }} حاليًا. يمكنك نشر لقاء جديد ودعوة الفريلانسرز إليه.
            @endif
        </p>
    </div>

    @if ($events->isEmpty())
        <div class="liqa-card text-center py-5 mb-4">
            <p class="fw-bold fs-5 mb-3">كن أول من ينشر لقاءً هنا</p>
            @auth
                <a href="{{ route('events.create') }}" class="btn btn-liqa px-4">انشر لقاءً</a>
            @else
                <a href="{{ route('register') }}" class="btn btn-liqa px-4">أنشئ حسابك</a>
            @endauth
        </div>
    @else
        <div class="row g-3">
            @foreach ($events as $event)
                <div class="col-md-6 col-lg-4"><x-event-card :event="$event" :user="auth()->user()" /></div>
            @endforeach
        </div>
        <div class="mt-4 d-flex justify-content-center">{{ $events->links() }}</div>
    @endif

    {{-- روابط داخلية --}}
    <div class="row g-4 mt-4">
        @if ($cities->isNotEmpty() && isset($filters['country']))
            <div class="col-md-6">
                <h2 class="h6 text-white mb-2">المدن في {{ $filters['country'] }}</h2>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($cities as $name => $total)
                        <a class="liqa-chip text-decoration-none" href="{{ route('events.city', [$filters['country'], $name]) }}">{{ $name }} ({{ $total }})</a>
                    @endforeach
                </div>
            </div>
        @endif
        @if (! isset($filters['country']) && $countries->isNotEmpty())
            <div class="col-md-6">
                <h2 class="h6 text-white mb-2">الأحداث بحسب الدولة</h2>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($countries as $name => $total)
                        <a class="liqa-chip text-decoration-none" href="{{ route('events.country', $name) }}">{{ $name }} ({{ $total }})</a>
                    @endforeach
                </div>
            </div>
        @endif
        @if ($typeLinks->isNotEmpty())
            <div class="col-md-6">
                <h2 class="h6 text-white mb-2">الأحداث بحسب المجال</h2>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($typeLinks as $key => $total)
                        @if (isset($types[$key]))
                            <a class="liqa-chip text-decoration-none" href="{{ route('events.type', $key) }}">{{ $types[$key] }} ({{ $total }})</a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
