@php
    $seoTitle = trim($__env->yieldContent('title', __('لقاء')));
    $seoDesc = trim($__env->yieldContent('description', __('لقاء: منصة لقاءات الفريلانسر المهنية — تعارف، لقاء، ونموّ مشترك.')));
    $seoImage = asset('brand/og-image.png');
@endphp
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_AR' : 'en_US' }}">
<meta property="og:site_name" content="{{ \App\Models\Setting::site('name') }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
<meta name="twitter:image" content="{{ $seoImage }}">
