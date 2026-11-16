@extends(auth()->check() ? 'layouts.app' : 'layouts.site')
@section('title', $title)
@section('content')
    <div class="mx-auto legal" style="max-width: 760px">
        <h1 class="h2 liqa-title mb-2">{{ $title }}</h1>
        <p class="text-secondary mb-4">{{ __('آخر تحديث: :v1', ['v1' => $updated->translatedFormat('j F Y')]) }}</p>
        @foreach ($blocks as [$kind, $text])
            @if ($kind === 'h')
                <h2 class="h5 text-white mt-4">{{ $text }}</h2>
            @else
                <p class="legal-p">{!! nl2br(e($text)) !!}</p>
            @endif
        @endforeach
    </div>
@endsection
