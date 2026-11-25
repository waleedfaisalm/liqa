<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\AdminBroadcastController;
use App\Http\Controllers\AdminCommentController;
use App\Http\Controllers\AdminExportController;
use App\Http\Controllers\AdminSecurityController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminTwoFactorController;
use App\Http\Controllers\AdminContentController;
use App\Http\Controllers\AdminLogController;
use App\Http\Controllers\AdminMessageController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AdminPagesController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\AdminRatingController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\EventCalendarController;
use App\Http\Controllers\EventCommentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavedEventController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| لقاء — Routes
|--------------------------------------------------------------------------
*/

// ---------- الصفحة الرئيسية (للزائر والعضو) ----------
Route::get('/lang/{locale}', [\App\Http\Controllers\LocaleController::class, 'set'])->name('lang');
Route::get('/', [\App\Http\Controllers\LandingController::class, 'index'])->name('landing');
Route::get('/verify/{code}', [\App\Http\Controllers\EventAttendanceController::class, 'verify'])->where('code', '[A-Za-z0-9-]{6,20}')->middleware('throttle:30,1')->name('certificate.verify');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap']);
// تشغيل التذكيرات من خدمة خارجية (cron-job.org مثلًا): /cron/reminders?key=CRON_SECRET
Route::get('/cron/weekly-report', [\App\Http\Controllers\SeoController::class, 'weeklyReport'])->middleware('throttle:6,1');
Route::get('/cron/reminders', [\App\Http\Controllers\SeoController::class, 'reminders'])->middleware('throttle:30,1');
Route::get('/avatar/{user}', [\App\Http\Controllers\AvatarController::class, 'show'])->whereNumber('user')->name('avatar');
Route::get('/privacy', [LegalPageController::class, 'show'])->defaults('type', 'privacy')->name('privacy');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:4,10')->name('contact.store');
Route::get('/terms', [LegalPageController::class, 'show'])->defaults('type', 'terms')->name('terms');

// ---------- الأحداث: متاحة للزائر والعضو (للعرض فقط للزائر) ----------
Route::middleware('onboarded')->group(function () {
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/in/{country}', [\App\Http\Controllers\DiscoverController::class, 'country'])->name('events.country');
    Route::get('/events/in/{country}/{city}', [\App\Http\Controllers\DiscoverController::class, 'city'])->name('events.city');
    Route::get('/events/type/{type}', [\App\Http\Controllers\DiscoverController::class, 'type'])->name('events.type');
    Route::get('/events/{event}', [EventController::class, 'show'])->whereNumber('event')->name('events.show');
});

// ---------- زائر (غير مسجّل دخول) ----------
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleController::class, 'callback'])->name('google.callback');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

// ---------- عضو مسجّل دخول (حتى لو لسه ما كمّلش Onboarding) ----------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // تفعيل البريد الإلكتروني
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:3,1')->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
        Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    });
});

// ---------- عضو كمّل Onboarding ----------
Route::middleware(['auth', 'verified', 'onboarded'])->group(function () {
    Route::get('/dashboard', [EventController::class, 'dashboard'])->name('home');

    // البروفايل والإعدادات
    Route::get('/profile', [ProfileController::class, 'me'])->name('profile');
    Route::get('/members', [\App\Http\Controllers\MembersController::class, 'index'])->name('members.index');
    Route::get('/users/{user}', [ProfileController::class, 'show'])->whereNumber('user')->name('users.show');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings/account', [SettingsController::class, 'destroy'])->middleware('throttle:5,1')->name('settings.destroy');
    Route::put('/settings/password', [SettingsController::class, 'password'])->name('settings.password');

    // الأحداث
    Route::get('/events/mine', [EventController::class, 'mine'])->name('events.mine');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->whereNumber('event')->name('events.edit');
    Route::put('/events/{event}', [EventController::class, 'update'])->whereNumber('event')->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->whereNumber('event')->name('events.destroy');

    // الغرفة المباشرة (صوت وفيديو)
    Route::get('/events/{event}/room', [\App\Http\Controllers\EventRoomController::class, 'show'])->whereNumber('event')->middleware('throttle:30,1')->name('events.room');
    Route::middleware('throttle:90,1')->group(function () {
        Route::post('/events/{event}/room/knock', [\App\Http\Controllers\EventRoomController::class, 'knock'])->whereNumber('event')->name('events.room.knock');
        Route::get('/events/{event}/room/status', [\App\Http\Controllers\EventRoomController::class, 'status'])->whereNumber('event')->name('events.room.status');
        Route::post('/events/{event}/room/token', [\App\Http\Controllers\EventRoomController::class, 'token'])->whereNumber('event')->name('events.room.token');
        Route::get('/events/{event}/room/waiting', [\App\Http\Controllers\EventRoomController::class, 'waiting'])->whereNumber('event')->name('events.room.waiting');
        Route::post('/events/{event}/room/decide', [\App\Http\Controllers\EventRoomController::class, 'decide'])->whereNumber('event')->name('events.room.decide');
        Route::post('/events/{event}/room/lobby', [\App\Http\Controllers\EventRoomController::class, 'lobby'])->whereNumber('event')->name('events.room.lobby');
        Route::post('/events/{event}/room/mutejoin', [\App\Http\Controllers\EventRoomController::class, 'muteJoin'])->whereNumber('event')->name('events.room.mutejoin');
        Route::post('/events/{event}/room/end', [\App\Http\Controllers\EventRoomController::class, 'end'])->whereNumber('event')->name('events.room.end');
        Route::post('/events/{event}/room/link', [\App\Http\Controllers\EventRoomController::class, 'link'])->whereNumber('event')->name('events.room.link');
        Route::post('/events/{event}/room/cohost', [\App\Http\Controllers\EventRoomController::class, 'cohost'])->whereNumber('event')->name('events.room.cohost');
        Route::post('/events/{event}/room/mute', [\App\Http\Controllers\EventRoomController::class, 'mute'])->whereNumber('event')->name('events.room.mute');
    });
    Route::post('/events/{event}/room/ping', [\App\Http\Controllers\EventAttendanceController::class, 'ping'])->whereNumber('event')->middleware('throttle:6,1')->name('events.room.ping');
    Route::get('/events/{event}/certificate', [\App\Http\Controllers\EventAttendanceController::class, 'certificate'])->whereNumber('event')->name('events.certificate');
    Route::post('/events/{event}/room/remove', [\App\Http\Controllers\EventRoomController::class, 'remove'])->whereNumber('event')->middleware('throttle:30,1')->name('events.room.remove');

    // طلبات الانضمام
    Route::post('/events/{event}/join', [EventRequestController::class, 'store'])->whereNumber('event')->middleware(['throttle:20,1', \App\Http\Middleware\BlockRestrictedJoin::class])->name('events.join');
    Route::delete('/events/{event}/join', [EventRequestController::class, 'destroy'])->whereNumber('event')->name('events.leave');
    Route::patch('/events/{event}/requests/{eventRequest}', [EventRequestController::class, 'decide'])
        ->whereNumber(['event', 'eventRequest'])->name('events.requests.decide');

    // الأحداث المحفوظة
    Route::get('/saved', [SavedEventController::class, 'index'])->name('saved.index');
    Route::post('/events/{event}/save', [SavedEventController::class, 'toggle'])
        ->whereNumber('event')->middleware('throttle:60,1')->name('events.save');

    // إضافة الحدث إلى التقويم
    Route::get('/events/{event}/calendar', [EventCalendarController::class, 'ics'])->whereNumber('event')->name('events.calendar');

    // التعليقات والأسئلة
    Route::post('/events/{event}/comments', [EventCommentController::class, 'store'])
        ->whereNumber('event')->middleware('throttle:15,1')->name('events.comments.store');
    Route::delete('/events/{event}/comments/{comment}', [EventCommentController::class, 'destroy'])
        ->whereNumber(['event', 'comment'])->name('events.comments.destroy');

    // البلاغات
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:6,1')->name('reports.store');

    // التقييم المتبادل بين المشاركين
    Route::get('/events/{event}/rate/{user}', [RatingController::class, 'create'])
        ->whereNumber(['event', 'user'])->name('events.rate.create');
    Route::post('/events/{event}/ratings/{user}', [RatingController::class, 'store'])
        ->whereNumber(['event', 'user'])->middleware('throttle:30,1')->name('events.rate');

    Route::get('/contacts', [\App\Http\Controllers\ContactRequestController::class, 'index'])->name('contacts.index');
    Route::post('/contacts/{user}', [\App\Http\Controllers\ContactRequestController::class, 'store'])->whereNumber('user')->middleware('throttle:20,1')->name('contacts.store');
    Route::patch('/contacts/requests/{contactRequest}', [\App\Http\Controllers\ContactRequestController::class, 'decide'])->whereNumber('contactRequest')->name('contacts.decide');
    Route::delete('/contacts/requests/{contactRequest}', [\App\Http\Controllers\ContactRequestController::class, 'cancel'])->whereNumber('contactRequest')->name('contacts.cancel');

    // الإشعارات
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifications', [NotificationController::class, 'clear'])->name('notifications.clear');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->whereNumber('notification')->name('notifications.destroy');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->whereNumber('notification')->name('notifications.open');
});

// ---------- لوحة الإدارة: منفصلة تمامًا بتسجيل دخول خاص بها ----------
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('throttle:6,1')->name('admin.login.store');
    Route::get('/two-factor', [AdminTwoFactorController::class, 'create'])->name('admin.two-factor');
    Route::post('/two-factor', [AdminTwoFactorController::class, 'store'])->middleware('throttle:6,1')->name('admin.two-factor.store');

    Route::middleware('admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');
        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/stats', [\App\Http\Controllers\AdminStatsController::class, 'index'])->middleware('admin.can:stats')->name('admin.stats');
        Route::get('/report', [\App\Http\Controllers\AdminStatsController::class, 'report'])->middleware('admin.can:stats')->name('admin.report');
        Route::get('/demand', [\App\Http\Controllers\AdminDemandController::class, 'index'])->middleware('admin.can:stats')->name('admin.demand');
        Route::post('/demand/sweep', [\App\Http\Controllers\AdminDemandController::class, 'sweep'])->middleware(['admin.can:stats', 'throttle:6,1'])->name('admin.demand.sweep');

        // أمان الحساب (لكل مدير)
        Route::get('/security', [AdminSecurityController::class, 'show'])->name('admin.security');
        Route::post('/security/enable', [AdminSecurityController::class, 'enable'])->middleware('throttle:10,1')->name('admin.security.enable');
        Route::post('/security/regenerate', [AdminSecurityController::class, 'regenerate'])->middleware('throttle:6,1')->name('admin.security.regenerate');
        Route::delete('/security/disable', [AdminSecurityController::class, 'disable'])->middleware('throttle:6,1')->name('admin.security.disable');

        Route::middleware('admin.can:users')->group(function () {
            Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
            Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
            Route::post('/users', [AdminUserController::class, 'store'])->middleware('throttle:20,1')->name('admin.users.store');
            Route::get('/users/{user}', [AdminUserController::class, 'show'])->whereNumber('user')->name('admin.users.show');
            Route::post('/users/{user}/nominate', [\App\Http\Controllers\AdminDemandController::class, 'nominate'])->whereNumber('user')->name('admin.users.nominate');
            Route::patch('/users/{user}/restrict', [\App\Http\Controllers\AdminDemandController::class, 'restrict'])->whereNumber('user')->name('admin.users.restrict');
            Route::patch('/users/{user}/unrestrict', [\App\Http\Controllers\AdminDemandController::class, 'unrestrict'])->whereNumber('user')->name('admin.users.unrestrict');
            Route::patch('/users/{user}/verify', [AdminUserController::class, 'verify'])->whereNumber('user')->name('admin.users.verify');
            Route::patch('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->whereNumber('user')->name('admin.users.suspend');
            Route::patch('/users/{user}/password', [AdminUserController::class, 'resetPassword'])->middleware('throttle:10,1')->whereNumber('user')->name('admin.users.password');
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->middleware('throttle:10,1')->whereNumber('user')->name('admin.users.destroy');
            Route::patch('/users/{user}/role', [AdminUserController::class, 'role'])->whereNumber('user')->name('admin.users.role');
            Route::patch('/users/{user}/permissions', [AdminUserController::class, 'permissions'])->whereNumber('user')->name('admin.users.permissions');
        });

        Route::middleware('admin.can:events')->group(function () {
            Route::get('/events', [AdminEventController::class, 'index'])->name('admin.events');
            Route::delete('/events/{event}', [AdminEventController::class, 'destroy'])->middleware('throttle:20,1')->whereNumber('event')->name('admin.events.destroy');
            Route::patch('/events/{event}/cancel', [AdminEventController::class, 'cancel'])->whereNumber('event')->name('admin.events.cancel');
        });

        Route::middleware('admin.can:comments')->group(function () {
            Route::get('/comments', [AdminCommentController::class, 'index'])->name('admin.comments');
            Route::get('/ratings', [AdminRatingController::class, 'index'])->name('admin.ratings');
            Route::delete('/ratings/{rating}', [AdminRatingController::class, 'destroy'])->whereNumber('rating')->name('admin.ratings.destroy');
            Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->whereNumber('comment')->name('admin.comments.destroy');
        });

        Route::middleware('admin.can:broadcast')->group(function () {
            Route::get('/broadcast', [AdminBroadcastController::class, 'create'])->name('admin.broadcast');
            Route::post('/broadcast', [AdminBroadcastController::class, 'store'])->middleware('throttle:5,1')->name('admin.broadcast.store');
        });

        Route::get('/export/{type}', AdminExportController::class)->middleware(['admin.can:export', 'throttle:10,1'])->name('admin.export');

        Route::get('/logs', [AdminLogController::class, 'index'])->middleware('admin.can:logs')->name('admin.logs');

        Route::middleware('admin.can:reports')->group(function () {
            Route::get('/reports', [AdminReportController::class, 'index'])->name('admin.reports');
            Route::get('/messages', [AdminMessageController::class, 'index'])->name('admin.messages');
            Route::patch('/messages/{message}/toggle', [AdminMessageController::class, 'toggle'])->whereNumber('message')->name('admin.messages.toggle');
            Route::delete('/messages/{message}', [AdminMessageController::class, 'destroy'])->whereNumber('message')->name('admin.messages.destroy');
            Route::patch('/reports/{report}', [AdminReportController::class, 'update'])->whereNumber('report')->name('admin.reports.update');
        });

        Route::middleware('admin.can:settings')->group(function () {
            Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('admin.settings');
            Route::put('/settings', [AdminSettingsController::class, 'update'])->name('admin.settings.update');
            Route::get('/content', fn () => redirect()->route('admin.content.identity'))->name('admin.content');
            Route::get('/content/identity', [AdminContentController::class, 'identity'])->name('admin.content.identity');
            Route::put('/content/identity', [AdminContentController::class, 'updateIdentity'])->name('admin.content.identity.update');
            Route::get('/content/landing', [AdminContentController::class, 'landing'])->name('admin.content.landing');
            Route::put('/content/landing', [AdminContentController::class, 'updateLanding'])->name('admin.content.landing.update');
            Route::get('/content/categories', [AdminContentController::class, 'categories'])->name('admin.content.categories');
            Route::put('/content/categories', [AdminContentController::class, 'updateCategories'])->name('admin.content.categories.update');
            Route::get('/content/places', [AdminContentController::class, 'places'])->name('admin.content.places');
            Route::put('/content/places', [AdminContentController::class, 'updatePlaces'])->name('admin.content.places.update');
            Route::get('/pages', [AdminPagesController::class, 'edit'])->name('admin.pages');
            Route::put('/pages/{type}', [AdminPagesController::class, 'update'])->name('admin.pages.update');
        });
    });
});
