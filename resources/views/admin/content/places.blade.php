@extends('layouts.admin')

@section('title', 'الدول والمدن')

@section('content')
    <div class="mb-3">
        <h1 class="h2 liqa-title mb-1">إدارة المحتوى</h1>
        <p class="text-secondary mb-0">الدول والمدن المتاحة عند نشر حدث حضوري. اكتب كل مدينة في سطر مستقل.</p>
    </div>
    @include('admin.content._tabs')

    @error('places') <div class="liqa-alert liqa-alert--err mb-3">{{ $message }}</div> @enderror
    @if ($errors->has('places.*')) <div class="liqa-alert liqa-alert--err mb-3">{{ $errors->first() }}</div> @endif

    <div class="liqa-alert mb-3 small" style="max-width: 820px">تغيير اسم دولة أو مدينة لا يعدّل الأحداث المنشورة سابقًا، فهي تحتفظ بالاسم الذي نُشرت به.</div>

    <form method="POST" action="{{ route('admin.content.places.update') }}" style="max-width: 820px">
        @csrf @method('PUT')
        <div id="places">
            @php $i = 0; @endphp
            @foreach ($places as $country => $cities)
                <div class="liqa-card p-3 mb-3 js-place">
                    <div class="d-flex gap-2 mb-2">
                        <input name="places[{{ $i }}][country]" value="{{ $country }}" maxlength="40" class="form-control" placeholder="اسم الدولة" aria-label="اسم الدولة" required>
                        <button type="button" class="btn btn-sm btn-liqa-danger px-3 js-del" onclick="return false">حذف</button>
                    </div>
                    <textarea name="places[{{ $i }}][cities]" rows="4" class="form-control" placeholder="المدن، مدينة في كل سطر" aria-label="المدن">{{ implode("\n", $cities) }}</textarea>
                </div>
                @php $i++; @endphp
            @endforeach
        </div>
        <button type="button" id="add-place" class="btn btn-sm btn-liqa-outline px-3 mb-3">+ إضافة دولة</button>
        <div><button class="btn btn-liqa px-4 mb-4">حفظ</button></div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    var box = document.getElementById('places');
    function reindex() {
        box.querySelectorAll('.js-place').forEach(function (card, i) {
            card.querySelector('input').name = 'places[' + i + '][country]';
            card.querySelector('textarea').name = 'places[' + i + '][cities]';
        });
    }
    box.addEventListener('click', function (e) {
        if (!e.target.classList.contains('js-del')) return;
        if (box.querySelectorAll('.js-place').length < 2) { alert('يجب أن تبقى دولة واحدة على الأقل.'); return; }
        if (confirm('حذف هذه الدولة ومدنها من القائمة؟')) { e.target.closest('.js-place').remove(); reindex(); }
    });
    document.getElementById('add-place').addEventListener('click', function () {
        var div = document.createElement('div');
        div.className = 'liqa-card p-3 mb-3 js-place';
        div.innerHTML = '<div class="d-flex gap-2 mb-2"><input maxlength="40" class="form-control" placeholder="اسم الدولة" aria-label="اسم الدولة" required><button type="button" class="btn btn-sm btn-liqa-danger px-3 js-del">حذف</button></div><textarea rows="4" class="form-control" placeholder="المدن، مدينة في كل سطر" aria-label="المدن"></textarea>';
        box.appendChild(div); reindex(); div.querySelector('input').focus();
    });
})();
</script>
@endpush
