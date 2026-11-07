@extends('layouts.admin')

@section('title', 'المجالات والتخصصات')

@section('content')
    <div class="mb-3">
        <h1 class="h2 liqa-title mb-1">إدارة المحتوى</h1>
        <p class="text-secondary mb-0">أضف مجالات الأحداث وتخصصات الأعضاء، وأعد تسميتها، وأخفِ ما لا تريده. الإخفاء لا يحذف البيانات القديمة.</p>
    </div>
    @include('admin.content._tabs')

    @error('types') <div class="liqa-alert liqa-alert--err mb-3">{{ $message }}</div> @enderror
    @error('specialties') <div class="liqa-alert liqa-alert--err mb-3">{{ $message }}</div> @enderror
    @if ($errors->has('types.*') || $errors->has('specialties.*')) <div class="liqa-alert liqa-alert--err mb-3">{{ $errors->first() }}</div> @endif

    <form method="POST" action="{{ route('admin.content.categories.update') }}" style="max-width: 720px">
        @csrf @method('PUT')

        @foreach ([['types', 'مجالات الأحداث', $types, 'مجال جديد'], ['specialties', 'تخصصات الأعضاء', $specialties, 'تخصص جديد']] as [$name, $title, $rows, $addLabel])
            <div class="liqa-card p-3 p-md-4 mb-3">
                <h2 class="h5 liqa-title mb-3">{{ $title }}</h2>
                <div class="js-list" data-name="{{ $name }}">
                    @foreach ($rows as $i => $row)
                        <div class="d-flex align-items-center gap-2 mb-2 js-row">
                            @if ($name === 'types') <input type="hidden" name="types[{{ $i }}][key]" value="{{ $row['key'] }}">
                            @else <input type="hidden" name="specialties[{{ $i }}][orig]" value="{{ $row['label'] }}"> @endif
                            <input name="{{ $name }}[{{ $i }}][label]" value="{{ old($name.'.'.$i.'.label', $row['label']) }}" maxlength="40" class="form-control" aria-label="الاسم">
                            <div class="form-check form-switch m-0 text-nowrap">
                                <input class="form-check-input" type="checkbox" role="switch" name="{{ $name }}[{{ $i }}][active]" value="1" id="{{ $name }}-{{ $i }}" @checked($row['active'])>
                                <label class="form-check-label small" for="{{ $name }}-{{ $i }}">ظاهر</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-liqa-outline px-2 js-up" title="أعلى">↑</button>
                            <button type="button" class="btn btn-sm btn-liqa-outline px-2 js-down" title="أسفل">↓</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-liqa-outline px-3 mt-2 js-add" data-target="{{ $name }}">+ إضافة {{ $addLabel }}</button>
            </div>
        @endforeach

        <button class="btn btn-liqa px-4 mb-4">حفظ</button>
    </form>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-list').forEach(function (list) {
    var name = list.dataset.name;
    function reindex() {
        list.querySelectorAll('.js-row').forEach(function (row, i) {
            row.querySelectorAll('input').forEach(function (inp) {
                inp.name = inp.name.replace(/^(types|specialties)\[\d+\]/, name + '[' + i + ']');
                if (inp.id) { inp.id = name + '-' + i; var l = row.querySelector('label'); if (l) l.htmlFor = inp.id; }
            });
        });
    }
    list.addEventListener('click', function (e) {
        var row = e.target.closest('.js-row'); if (!row) return;
        if (e.target.classList.contains('js-up') && row.previousElementSibling) { list.insertBefore(row, row.previousElementSibling); reindex(); }
        if (e.target.classList.contains('js-down') && row.nextElementSibling) { list.insertBefore(row.nextElementSibling, row); reindex(); }
    });
    document.querySelector('.js-add[data-target="' + name + '"]').addEventListener('click', function () {
        var i = list.querySelectorAll('.js-row').length;
        var div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-2 mb-2 js-row';
        div.innerHTML = '<input name="' + name + '[' + i + '][label]" maxlength="40" class="form-control" aria-label="الاسم" required>' +
            '<div class="form-check form-switch m-0 text-nowrap"><input class="form-check-input" type="checkbox" role="switch" name="' + name + '[' + i + '][active]" value="1" id="' + name + '-' + i + '" checked><label class="form-check-label small" for="' + name + '-' + i + '">ظاهر</label></div>' +
            '<button type="button" class="btn btn-sm btn-liqa-outline px-2 js-up">↑</button><button type="button" class="btn btn-sm btn-liqa-outline px-2 js-down">↓</button>';
        list.appendChild(div); div.querySelector('input').focus();
    });
});
</script>
@endpush
