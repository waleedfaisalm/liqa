@extends('layouts.app')

@section('title', $title)

@section('content')
    <h1 class="h2 liqa-title mb-4">{{ $title }}</h1>
    <div class="liqa-card text-center py-5">
        <p class="mb-1 fw-bold fs-5">هذه الصفحة قيد التنفيذ</p>
        <p class="mb-4 text-secondary">ستُبنى في مرحلتها وفق خطة العمل.</p>
        <a href="{{ route('home') }}" class="btn btn-liqa-outline px-4">العودة إلى لوحة التحكم</a>
    </div>
@endsection
