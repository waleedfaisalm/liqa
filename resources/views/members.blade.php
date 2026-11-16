@extends('layouts.app')
@section('title', __('الأعضاء'))
@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">{{ __('دليل') }} <span class="text-gradient">{{ __('الأعضاء') }}</span></h1>
        <p class="text-secondary mb-0">{{ __('ابحث عن مستقلين بحسب الاسم أو التخصص، وتعرّف على تقييماتهم وشاراتهم.') }}</p>
    </div>

    <form method="GET" action="{{ route('members.index') }}" class="liqa-card p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="liqa-label d-block small" for="q">{{ __('بحث') }}</label>
                <input id="q" type="search" name="q" value="{{ $q }}" maxlength="60" placeholder="{{ __('اسم أو تخصص أو كلمة من النبذة') }}" class="form-control">
            </div>
            <div class="col-6 col-md-3">
                <label class="liqa-label d-block small" for="specialty">{{ __('التخصص') }}</label>
                <select id="specialty" name="specialty" class="form-select">
                    <option value="">{{ __('كل التخصصات') }}</option>
                    @foreach ($specialties as $sp)
                        <option value="{{ $sp }}" @selected($specialty === $sp)>{{ $sp }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="liqa-label d-block small" for="sort">{{ __('الترتيب') }}</label>
                <select id="sort" name="sort" class="form-select">
                    @foreach ($sorts as $k => $label)
                        <option value="{{ $k }}" @selected($sort === $k)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-liqa">{{ __('بحث') }}</button>
            </div>
        </div>
    </form>

    @if ($members->isEmpty())
        <div class="liqa-card p-5 text-center">
            <p class="text-secondary mb-3">{{ __('لا يوجد أعضاء مطابقون لبحثك.') }}</p>
            <a href="{{ route('members.index') }}" class="btn btn-liqa-outline">{{ __('مسح البحث') }}</a>
        </div>
    @else
        <div class="row g-3">
            @foreach ($members as $m)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ $m->is(auth()->user()) ? route('profile') : route('users.show', $m) }}" class="member-card text-decoration-none">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <x-avatar :user="$m" :size="56" />
                            <div class="min-w-0">
                                <div class="fw-bold text-white text-truncate">{{ $m->name }} @if ($m->is(auth()->user()))<span class="small text-secondary">{{ __('(أنت)') }}</span>@endif</div>
                                @if ($m->specialty)<span class="liqa-badge">{{ __($m->specialty) }}</span>@endif
                            </div>
                        </div>

                        <p class="small text-secondary mb-2 member-card__bio">{{ $m->bio ?: __('لا توجد نبذة.') }}</p>

                        <div class="d-flex align-items-center justify-content-between small mb-2">
                            <span class="d-inline-flex align-items-center gap-1 text-white">
                                <x-stars :value="$m->avg_rating ?? 0" :size="15" />
                                <span>{{ $m->avg_rating !== null ? round($m->avg_rating, 1) : '—' }}</span>
                                <span class="text-secondary">({{ $m->ratings_count }})</span>
                            </span>
                            <span class="text-secondary">{{ __(':v1 نشر · :v2 حضور', ['v1' => $m->hosted_count, 'v2' => $m->attended_count]) }}</span>
                        </div>

                        @if (count($m->badge_list))
                            <div class="badge-list">
                                @foreach (array_slice($m->badge_list, 0, 3) as $b)
                                    <span class="badge-pill badge-pill--t{{ $b['tier'] }}" title="{{ $b['desc'] }}">{{ $b['icon'] }} {{ $b['label'] }}</span>
                                @endforeach
                            </div>
                        @endif
                    </a>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">{{ $members->links() }}</div>
    @endif
@endsection
