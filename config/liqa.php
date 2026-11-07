<?php

return [
    // وضع التجربة: تفعيل الحسابات الجديدة تلقائيًا (للاستضافة التي لا ترسل بريدًا)
    'auto_verify' => (bool) env('LIQA_AUTO_VERIFY', false),
];
