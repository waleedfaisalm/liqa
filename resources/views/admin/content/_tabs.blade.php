<div class="tab-pills mb-4">
    @foreach ([
        ['admin.content.identity', 'هوية المنصة', 'admin.content.identity*'],
        ['admin.content.landing', 'نصوص الرئيسية', 'admin.content.landing*'],
        ['admin.content.categories', 'المجالات والتخصصات', 'admin.content.categories*'],
        ['admin.content.places', 'الدول والمدن', 'admin.content.places*'],
        ['admin.pages', 'الصفحات القانونية', 'admin.pages*'],
    ] as [$r, $label, $m])
        <a href="{{ route($r) }}" class="tab-pill {{ request()->routeIs($m) ? 'is-active' : '' }}">{{ $label }}</a>
    @endforeach
</div>
