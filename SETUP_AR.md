# لقاء — تشغيل المشروع عندك

المشروع ده Laravel + Bootstrap 5 (نسخة RTL). المرحلة ٠ جاهزة: الهيكل، الألوان، الخط، شريط التنقل السفلي، وصفحة معاينة للتصميم.

## المطلوب على جهازك
- PHP 8.3 أو أحدث
- Composer
- Node.js 20 أو أحدث

## خطوات التشغيل (مرة واحدة)

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
```

## التشغيل كل يوم

في تيرمينالين:

```bash
php artisan serve
```

```bash
npm run dev
```

افتح http://localhost:8000 وهتلاقي معاينة التصميم (الكروت والأزرار والحقول).

## الهيكل

| المكان | فيه إيه |
| --- | --- |
| `resources/css/app.css` | ألوان وتصميم التطبيق كله (Design tokens) |
| `resources/views/layouts/app.blade.php` | قالب الشاشات اللي بعد الدخول (فيه الشريط السفلي) |
| `resources/views/layouts/guest.blade.php` | قالب شاشات الدخول والتسجيل |
| `resources/views/partials/nav.blade.php` | الشريط السفلي |
| `resources/views/design.blade.php` | صفحة معاينة المكونات (مؤقتة) |
| `routes/web.php` | المسارات |

## القرارات المعتمدة (من مستند المتطلبات)
- الاسم: لقاء
- المكان بيظهر لكل الناس
- أي عضو يقدر ينشر حدث
- التخصصات من قائمة ثابتة
- أي بلد عربي
- لقاءات أونلاين وحضورية
- التقييم متبادل بين المنظّم والمنضم
- موافقة المنظّم يدوية (افتراض، مفيش رد على السؤال ده)

## الخطوة الجاية
المرحلة ١: شاشات الدخول (تسجيل حساب، تسجيل دخول، نسيت كلمة السر، Onboarding).

---

## تحديث: قاعدة البيانات الجديدة + Onboarding + البروفايل

بعد فك ضغط التحديث فوق المشروع، نفّذ في PowerShell جوه فولدر المشروع:

```
php artisan migrate:fresh --seed
php artisan storage:link
```

- `migrate:fresh` بيمسح قاعدة البيانات القديمة ويبنيها من جديد بالجداول الجديدة (المستخدمين المسجّلين قبل كده هيتمسحوا).
- `--seed` بيضيف ٤ حسابات تجريبية (للتطوير المحلي بس)، كلمة السر لكل واحد: `password`
  - maha@liqa.test
  - karim@liqa.test
  - noor@liqa.test
  - youssef@liqa.test
- `storage:link` ضروري عشان صور البروفايل تظهر.

### التأكد إن الباك مربوط بقاعدة البيانات

```
php artisan db:table users
php artisan db:table events
```

---

## تحديث: الأحداث (نشر، تعديل، حذف، انضمام، قبول/رفض)

مفيش migration جديد، فبعد فك الضغط (Replace) أعد تشغيل `php artisan serve` بس. حسابات تجريبية (كلمة السر `password`): maha / karim / noor / youssef @liqa.test

- نشر حدث: `/events/create` — تعديل: زرار "تعديل الحدث" في صفحة الحدث (قبل موعده بس).
- حذف: لو ما حدش طلب الانضمام بيتحذف نهائيًا، ولو في طلبات بيتلغي والناس بتتخطر.
- الانضمام: "اطلب الانضمام" في صفحة الحدث، والمنظّم يقبل أو يرفض من قسم "طلبات الانضمام".
