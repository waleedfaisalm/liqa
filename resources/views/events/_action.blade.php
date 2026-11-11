    <div class="liqa-card p-3 p-md-4 mb-3 text-center">
        @if ($isHost)
            <span class="host-icon mb-3"><x-icon name="star" :size="28" /></span>
            <div class="fw-bold text-white mb-1">أنت المنظّم</div>
            <div class="small hint mb-3">إدارة الطلبات والتعديل من هنا</div>

            @include('events._room_btn', ['event' => $event])

            @if ($open)
                <a href="{{ route('events.edit', $event) }}" class="btn btn-liqa w-100 mb-2 d-flex align-items-center justify-content-center gap-2">
                    تعديل الحدث <x-icon name="edit" :size="17" />
                </a>
            @endif
            <a href="{{ route('events.mine') }}" class="btn btn-liqa-outline w-100 mb-2 d-flex align-items-center justify-content-center gap-2">
                أحداثي <x-icon name="calendar" :size="17" />
            </a>
            @if ($open)
                <form method="POST" action="{{ route('events.destroy', $event) }}"
                      onsubmit="return confirm('هل أنت متأكد من حذف الحدث؟ إذا وُجدت طلبات انضمام فسيتم إلغاؤه وإشعار أصحابها.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-liqa-danger w-100">حذف الحدث</button>
                </form>
            @endif
        @else
            <a href="{{ route('users.show', $event->host) }}" class="text-decoration-none d-block mb-3">
                <span class="d-block mb-2"><x-avatar :user="$event->host" :size="64" /></span>
                <span class="d-block fw-bold text-white">{{ $event->host->name }}</span>
                <span class="d-block small text-secondary">{{ $event->host->specialty }} · المنظّم</span>
            </a>

            <div class="seat-meter mb-3"><span style="width: {{ $percent }}%"></span></div>

            @if ($event->isCancelled())
                <button class="btn btn-liqa w-100" disabled>الحدث ملغي</button>
            @elseif (! auth()->check())
                <a href="{{ route('login') }}" class="btn btn-liqa w-100 mb-2">سجّل دخولك لطلب الانضمام</a>
                <a href="{{ route('register') }}" class="btn btn-liqa-outline w-100">إنشاء حساب جديد</a>
            @elseif ($myRequest)
                <div class="mb-3 d-flex align-items-center justify-content-center gap-2">
                    <span class="small text-secondary">حالة طلبك:</span>
                    <span class="liqa-badge {{ $statusClasses[$myRequest->status] }}">{{ $statusLabels[$myRequest->status] }}</span>
                </div>
                @if ($myRequest->status === 'waitlisted')
                    <p class="small text-secondary mb-3">ترتيبك في قائمة الانتظار: <strong class="text-white">{{ $waitlistPosition }}</strong>. سننقل طلبك إلى المنظّم فور توفّر مقعد.</p>
                @endif
                @if ($myRequest->status === 'accepted')
                    @include('events._room_btn', ['event' => $event])
                @endif
                @if ($myRequest->status !== 'rejected' && ! $started)
                    <form method="POST" action="{{ route('events.leave', $event) }}"
                          onsubmit="return confirm('{{ $myRequest->status === 'accepted' ? 'هل تريد الانسحاب من الحدث؟' : ($myRequest->status === 'waitlisted' ? 'هل تريد الخروج من قائمة الانتظار؟' : 'هل تريد سحب طلبك؟') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-liqa-outline w-100">{{ $myRequest->status === 'accepted' ? 'انسحاب من الحدث' : ($myRequest->status === 'waitlisted' ? 'الخروج من قائمة الانتظار' : 'سحب الطلب') }}</button>
                    </form>
                @endif
            @elseif ($started)
                <button class="btn btn-liqa w-100" disabled>بدأ الحدث</button>
            @elseif ($event->isFull())
                <form method="POST" action="{{ route('events.join', $event) }}">
                    @csrf
                    <button type="submit" class="btn btn-liqa w-100">انضم إلى قائمة الانتظار</button>
                </form>
                <p class="small text-secondary mt-2 mb-0">اكتمل العدد. سنُعلمك إذا توفّر مقعد.</p>
            @else
                <form method="POST" action="{{ route('events.join', $event) }}">
                    @csrf
                    <button type="submit" class="btn btn-liqa w-100">اطلب الانضمام</button>
                </form>
                <p class="small text-secondary mt-2 mb-0">يقرّر المنظّم قبول طلبك أو رفضه.</p>
            @endif
        @endif
    </div>
