@extends('layouts.app')

@section('title', 'معاينة التصميم')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <span class="small text-secondary">أهلاً يا أحمد</span>
            <h1 class="h3 liqa-title mb-0">الأحداث الجاية</h1>
        </div>
        <a href="{{ route('notifications') }}" class="liqa-icon-btn" aria-label="الإشعارات">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 16V11a6 6 0 0112 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 004 0"/></svg>
        </a>
    </div>

    <div class="d-flex gap-2 mb-3 overflow-hidden">
        <span class="liqa-chip is-active">الكل</span>
        <span class="liqa-chip">النهاردة</span>
        <span class="liqa-chip">الأسبوع ده</span>
        <span class="liqa-chip">قريب مني</span>
    </div>

    <article class="liqa-card mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="liqa-badge">نتووركنج</span>
            <span class="small text-secondary">٤ من ٨ مقاعد</span>
        </div>
        <h2 class="h5 liqa-title mb-2">لقاء الفريلانسرز الأسبوعي</h2>
        <p class="small mb-1" style="color:#c9c4ee">كافيه X · وسط البلد</p>
        <p class="small mb-3" style="color:#c9c4ee">الجمعة · ٦:٠٠ م</p>
        <div class="d-flex justify-content-between align-items-center pt-3 border-top" style="border-color: var(--liqa-border) !important">
            <span class="d-flex align-items-center gap-2 small">
                <span class="liqa-avatar" style="width:28px;height:28px;font-size:.75rem">م</span>
                مها خالد
            </span>
            <button type="button" class="btn btn-liqa btn-sm px-3" style="min-height:2.25rem">اطلب انضمام</button>
        </div>
    </article>

    <article class="liqa-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="liqa-badge liqa-badge--sky">تبادل خبرات</span>
            <span class="liqa-badge liqa-badge--green">أونلاين</span>
        </div>
        <h2 class="h5 liqa-title mb-0">جلسة تبادل خبرات للمطورين</h2>
    </article>

    <div class="liqa-panel mb-3">
        <label class="liqa-label d-block" for="demo">عنوان الحدث</label>
        <input id="demo" class="form-control" placeholder="مثلاً: لقاء الفريلانسرز الأسبوعي">
    </div>

    <div class="d-grid gap-2">
        <button class="btn btn-liqa">زرار أساسي</button>
        <button class="btn btn-liqa-outline">زرار ثانوي</button>
        <button class="btn btn-liqa-danger">إلغاء الحدث</button>
    </div>

    <a href="{{ route('events.create') }}" class="btn btn-liqa liqa-fab">+ نشر حدث</a>
@endsection
