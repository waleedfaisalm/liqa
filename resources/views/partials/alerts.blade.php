@if (session('success'))
    <div class="liqa-alert liqa-alert--ok js-flash mb-3" role="status">{{ session('success') }}</div>
@endif

@if (session('status'))
    <div class="liqa-alert liqa-alert--ok js-flash mb-3" role="status">{{ session('status') }}</div>
@endif

@if (session('error'))
    <div class="liqa-alert liqa-alert--err mb-3" role="alert">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="liqa-alert liqa-alert--err mb-3" role="alert">
        توجد بيانات تحتاج إلى تصحيح، يرجى مراجعة الحقول المعلّمة بالأحمر.
    </div>
@endif
