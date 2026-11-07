@extends('layouts.app')

@section('title', 'بروفايلي')

@section('content')
    <h1 class="h2 liqa-title mb-4">بروفايلي</h1>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="dash-profile text-center">
                <span class="liqa-avatar mb-3" style="width:84px;height:84px;font-size:2rem">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <div class="h5 liqa-title mb-1">{{ auth()->user()->name }}</div>
                <div class="small text-secondary text-break" dir="ltr">{{ auth()->user()->email }}</div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="liqa-card text-center py-5 h-100 d-flex flex-column justify-content-center">
                <p class="mb-1 fw-bold fs-5">باقي البروفايل لسه تحت التنفيذ</p>
                <p class="mb-0 text-secondary">التخصص والنبذة والتقييمات هتتضاف في المراحل الجاية.</p>
            </div>
        </div>
    </div>
@endsection
