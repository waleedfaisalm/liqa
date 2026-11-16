@php $isAr = app()->getLocale() === 'ar'; @endphp
<a href="{{ route('lang', $isAr ? 'en' : 'ar') }}" class="lang-switch" hreflang="{{ $isAr ? 'en' : 'ar' }}" lang="{{ $isAr ? 'en' : 'ar' }}" title="{{ $isAr ? 'English' : 'العربية' }}">{{ $isAr ? 'EN' : 'عربي' }}</a>
