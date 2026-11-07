@php $__announcement = \App\Models\Setting::get('announcement'); @endphp
@if ($__announcement)
    <div class="site-announcement" role="status">{{ $__announcement }}</div>
@endif
