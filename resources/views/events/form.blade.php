@extends('layouts.app')
@php
    $editing = $event !== null;
    $val = fn ($key, $default = null) => old($key, $editing ? $event->{$key} : $default);
    $startsValue = old('starts_at', $editing ? $event->starts_at->format('Y-m-d\TH:i') : '');
    $currentMode = $val('mode', 'in_person');
    $minCap = $editing ? max(2, $event->accepted_requests_count) : 2;
    $bio = (string) $val('description', '');
@endphp

@section('title', $editing ? __('تعديل الحدث') : __('نشر حدث'))
@section('content')
    <div class="mx-auto" style="max-width: 760px">
        <div class="form-card">
            <span class="eyebrow mb-3"><span class="dot"></span>{{ $editing ? __('تعديل الحدث') : __('نشر حدث جديد') }}</span>
            <h1 class="h2 liqa-title mb-1">
                @if ($editing) عدّل <span class="text-gradient">{{ __('لقاءك') }}</span>
                @else انشر <span class="text-gradient">{{ __('لقاءً جديدًا') }}</span>
                @endif
            </h1>
            <p class="hint mb-4">
                {{ $editing ? __('سيصل المشاركين المقبولين إشعار بالتعديل.') : __('حدّد المكان والموعد، وستتولى المنصة إيصالك بالمشاركين.') }}
            </p>

            <form method="POST" action="{{ $editing ? route('events.update', $event) : route('events.store') }}" novalidate class="d-grid gap-4">
                @csrf
                @if ($editing) @method('PUT') @endif

                <div>
                    <label class="liqa-label d-block" for="title">{{ __('عنوان الحدث') }} <span class="req">*</span></label>
                    <input id="title" type="text" name="title" value="{{ $val('title') }}" maxlength="80" required
                           class="form-control @error('title') is-invalid @enderror" placeholder="{{ __('مثال: لقاء مطوّري الواجهات الأمامية (Frontend)') }}">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="liqa-label d-block" for="description">{{ __('الوصف') }} <span class="req">*</span></label>
                    <textarea id="description" name="description" rows="4" maxlength="600" required
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="{{ __('ما محاور اللقاء؟ وما طبيعة الأجواء المتوقعة؟') }}">{{ $bio }}</textarea>
                    <div class="hint mt-2">{{ __('الحد الأقصى 600 حرف') }} <span id="desc-count" class="text-secondary"></span></div>
                    @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="liqa-label d-block" for="type">{{ __('التصنيف') }} <span class="req">*</span></label>
                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                            <option value="" disabled @selected(! $val('type'))>{{ __('اختر التصنيف...') }}</option>
                            @foreach (collect(\App\Support\Catalog::eventTypes())->union($editing && $event->type ? [$event->type => \App\Support\Catalog::eventTypeLabel($event->type)] : []) as $key => $label)
                                <option value="{{ $key }}" @selected($val('type') === $key)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="liqa-label d-block" for="mode">{{ __('طريقة الحدث') }} <span class="req">*</span></label>
                        <select id="mode" name="mode" class="form-select @error('mode') is-invalid @enderror">
                            @foreach (\App\Models\Event::MODES as $key => $label)
                                <option value="{{ $key }}" @selected($currentMode === $key)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        @error('mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6 geo-field" @if ($currentMode === 'online') hidden @endif>
                        <label class="liqa-label d-block" for="country">{{ __('الدولة') }} <span class="req">*</span></label>
                        <select id="country" name="country" class="form-select @error('country') is-invalid @enderror">
                            <option value="" disabled @selected(! $val('country'))>{{ __('اختر الدولة...') }}</option>
                            @foreach (array_keys(\App\Support\Catalog::places()) as $countryName)
                                <option value="{{ $countryName }}" @selected($val('country') === $countryName)>{{ __($countryName) }}</option>
                            @endforeach
                        </select>
                        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 geo-field" @if ($currentMode === 'online') hidden @endif>
                        <label class="liqa-label d-block" for="city">{{ __('المدينة') }} <span class="req">*</span></label>
                        <select id="city" name="city" class="form-select @error('city') is-invalid @enderror"></select>
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12" id="city-other-wrap" hidden>
                        <label class="liqa-label d-block" for="city_other">{{ __('اسم المدينة') }} <span class="req">*</span></label>
                        <input id="city_other" type="text" name="city_other" value="{{ old('city_other') }}" maxlength="60"
                               class="form-control @error('city_other') is-invalid @enderror" placeholder="{{ __('اكتب اسم المدينة') }}">
                        @error('city_other') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12" id="location-wrap">
                        <label class="liqa-label d-block" for="location" id="location-label">
                            {{ $currentMode === 'online' ? __('رابط اللقاء أو المنصة') : __('المكان بالتحديد') }} <span class="req">*</span>
                        </label>
                        <input id="location" type="text" name="location" value="{{ $val('location') }}" maxlength="150" required
                               class="form-control @error('location') is-invalid @enderror"
                               placeholder="{{ $currentMode === 'online' ? 'https://meet.google.com/...' : __('اسم المكان وعنوانه') }}">
                        @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="liqa-label d-block" for="starts_at">{{ __('التاريخ والوقت') }} <span class="req">*</span></label>
                        <input id="starts_at" type="datetime-local" name="starts_at" value="{{ $startsValue }}" min="{{ now()->format('Y-m-d\TH:i') }}" required
                               class="form-control @error('starts_at') is-invalid @enderror">
                        @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="liqa-label d-block" for="duration_minutes">{{ __('المدة') }} <span class="req">*</span></label>
                        <select id="duration_minutes" name="duration_minutes" class="form-select @error('duration_minutes') is-invalid @enderror">
                            @foreach (\App\Models\Event::DURATIONS as $minutes => $label)
                                <option value="{{ $minutes }}" @selected((int) $val('duration_minutes', 120) === $minutes)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        @error('duration_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="liqa-label d-block" for="capacity">{{ __('عدد المشاركين') }} <span class="req">*</span></label>
                        <input id="capacity" type="number" name="capacity" value="{{ $val('capacity', 8) }}" min="{{ $minCap }}" max="100" required
                               class="form-control @error('capacity') is-invalid @enderror">
                        @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                @unless ($editing)
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="liqa-label d-block" for="repeat">{{ __('التكرار') }}</label>
                            <select id="repeat" name="repeat" class="form-select">
                                @foreach (['none' => __('لقاء واحد (بلا تكرار)'), 'weekly' => __('أسبوعيًا'), 'biweekly' => __('كل أسبوعين'), 'monthly' => __('شهريًا')] as $k => $label)
                                    <option value="{{ $k }}" @selected(old('repeat', 'none') === $k)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6" id="occ-wrap" @if (old('repeat', 'none') === 'none') hidden @endif>
                            <label class="liqa-label d-block" for="occurrences">{{ __('عدد اللقاءات في السلسلة') }} <span class="req">*</span></label>
                            <input id="occurrences" type="number" name="occurrences" value="{{ old('occurrences', 4) }}" min="2" max="12"
                                   class="form-control @error('occurrences') is-invalid @enderror">
                            @error('occurrences') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="hint">{{ __('يُنشأ كل لقاء منفصلًا (بطلبات انضمام خاصة به)، ويمكنك تعديل أو إلغاء أي منها لاحقًا.') }}</div>
                        </div>
                    </div>
                @endunless
                @if ($editing && $event->accepted_requests_count > 0)
                    <div class="hint mt-n2">{{ __('الحد الأدنى :v1 لوجود :v2 مشاركين مقبولين.', ['v1' => $minCap, 'v2' => $event->accepted_requests_count]) }}</div>
                @endif

                <div class="d-flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-liqa px-5">{{ $editing ? __('حفظ التعديلات') : __('نشر الحدث') }}</button>
                    <a href="{{ $editing ? route('events.show', $event) : route('events.index') }}" class="btn btn-liqa-outline px-4 d-flex align-items-center">{{ __('إلغاء') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    var mode = document.getElementById('mode');
    var desc = document.getElementById('description');
    var count = document.getElementById('desc-count');

    function syncMode() {
        var online = mode.value === 'online';
        document.querySelectorAll('.geo-field').forEach(function (el) { el.hidden = online; });
        if (online) { document.getElementById('city-other-wrap').hidden = true; } else { syncCityOther(); }
        document.getElementById('location-label').firstChild.textContent = online ? {{ Js::from(__('رابط اللقاء أو المنصة').' ') }} : {{ Js::from(__('المكان بالتحديد').' ') }};
        document.getElementById('location').placeholder = online ? 'https://meet.google.com/...' : {{ Js::from(__('اسم المكان وعنوانه')) }};
    }
    var repeatSel = document.getElementById('repeat');
    if (repeatSel) {
        repeatSel.addEventListener('change', function () { document.getElementById('occ-wrap').hidden = repeatSel.value === 'none'; });
    }
    function syncCount() { count.textContent = '· ' + desc.value.length + ' / 600'; }

    // ---- الدولة والمدينة ----
    var PLACES = @json(\App\Support\Catalog::places());
    var CITY_T = @json(collect(\App\Support\Catalog::places())->flatten()->unique()->mapWithKeys(fn ($c) => [$c => __($c)]));
    var countrySel = document.getElementById('country');
    var citySel = document.getElementById('city');
    var otherWrap = document.getElementById('city-other-wrap');
    var initialCity = @json(old('city', $editing ? $event->city : null));

    function fillCities(country, selected) {
        var cities = PLACES[country] || [];
        citySel.innerHTML = '';
        var ph = document.createElement('option');
        ph.value = ''; ph.disabled = true; ph.textContent = country ? {{ Js::from(__('اختر المدينة...')) }} : {{ Js::from(__('اختر الدولة أولًا')) }};
        citySel.appendChild(ph);
        cities.forEach(function (c) {
            var o = document.createElement('option'); o.value = c; o.textContent = CITY_T[c] || c; citySel.appendChild(o);
        });
        if (country) {
            var other = document.createElement('option'); other.value = '__other'; other.textContent = {{ Js::from(__('مدينة أخرى...')) }}; citySel.appendChild(other);
        }
        if (selected && (cities.indexOf(selected) !== -1 || selected === '__other')) {
            citySel.value = selected;
        } else if (selected && country) {
            citySel.value = '__other';
            document.getElementById('city_other').value = document.getElementById('city_other').value || selected;
        } else {
            citySel.value = '';
        }
        syncCityOther();
    }
    function syncCityOther() {
        otherWrap.hidden = mode.value === 'online' || citySel.value !== '__other';
    }

    countrySel.addEventListener('change', function () { fillCities(countrySel.value, null); });
    citySel.addEventListener('change', syncCityOther);
    fillCities(countrySel.value, initialCity);

    mode.addEventListener('change', syncMode);
    desc.addEventListener('input', syncCount);
    syncMode(); syncCount();
</script>
@endpush
