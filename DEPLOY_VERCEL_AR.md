# نشر «لقاء» للتجربة على Vercel

> ميزة «Container Runtime» في Vercel ما زالت Beta وتعمل على كل الخطط. المنصة تعمل داخل حاوية FrankenPHP.
> الملفات المكتوبة داخل الحاوية غير دائمة (الصور المرفوعة لن تُحفظ)، والبيانات تُحفظ في قاعدة Postgres خارجية.

## 1) رفع المشروع إلى GitHub
```
git init
git add .
git status      # تأكد أن .env لا يظهر
git commit -m "الإصدار الأول من منصة لقاء"
git branch -M main
git remote add origin https://github.com/USERNAME/liqa.git
git push -u origin main
```

## 2) مفتاح التطبيق (على جهازك)
```
php artisan key:generate --show
```
انسخ الناتج كاملًا (يبدأ بـ base64:).

## 3) إنشاء المشروع على Vercel
1. vercel.com ← Add New ← Project ← استورد مستودع liqa من GitHub.
2. اترك الإعدادات كما هي (يكتشف vercel.json والحاوية تلقائيًا). لا تضغط Deploy بعد، أضف المتغيرات أولًا (الخطوة 5).

## 4) قاعدة البيانات (Postgres مجانية)
1. في المشروع: Storage ← Create Database ← اختر Neon (Postgres) من Marketplace ← الخطة المجانية ← اربطها بالمشروع.
2. افتح الاتصال وانسخ رابط الاتصال «غير المجمّع» (Unpooled / Direct)، شكله:
   `postgres://user:password@host/dbname?sslmode=require`

## 5) متغيرات البيئة (Settings ← Environment Variables)
| المتغير | القيمة |
|---|---|
| APP_KEY | المفتاح من الخطوة 2 |
| APP_URL | رابط الموقع، مثل https://liqa.vercel.app (عدّله بعد أول نشر) |
| DB_CONNECTION | pgsql |
| DB_URL | رابط Neon من الخطوة 4 |
| DB_SSLMODE | require |
| OWNER_EMAIL | بريدك (حساب المالك) |
| OWNER_PASSWORD | كلمة سر قوية للمالك |
| LIQA_AUTO_VERIFY | true |
| LIQA_DEMO | 1 |
| MAIL_MAILER | log |

## 6) النشر
اضغط Deploy. عند كل تشغيل للحاوية تُنشأ الجداول وحساب المالك والبيانات التجريبية تلقائيًا.
افتح الرابط، ولوحة الإدارة على `/admin/login`.

## ملاحظات
- أول طلب بعد الخمول (5 دقائق بلا زيارات) أبطأ لأن الحاوية تُشغَّل من جديد.
- لا يعمل إرسال البريد في وضع التجربة، وتُفعَّل الحسابات تلقائيًا.
- المهام المجدولة (التذكيرات والنسخ الاحتياطي) غير مفعّلة هنا.
- الصور الشخصية المرفوعة لا تُحفظ (تظهر الأحرف الأولى بدلًا منها).
