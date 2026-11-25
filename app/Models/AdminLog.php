<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLog extends Model
{
    public $timestamps = false;

    /** الإجراء => [الوصف بالعربية، صنف الوسم] */
    public const ACTIONS = [
        'admin.login' => ['تسجيل دخول مدير', ''],
        'admin.login_failed' => ['محاولة دخول فاشلة', 'liqa-badge--red'],
        'admin.logout' => ['تسجيل خروج مدير', ''],
        'user.created' => ['إنشاء مستخدم', 'liqa-badge--green'],
        'user.suspended' => ['إيقاف مستخدم', 'liqa-badge--red'],
        'user.unsuspended' => ['إعادة تفعيل مستخدم', 'liqa-badge--green'],
        'user.role_granted' => ['منح صلاحية مدير', 'liqa-badge--pink'],
        'user.role_revoked' => ['سحب صلاحية مدير', 'liqa-badge--pink'],
        'user.verified' => ['توثيق حساب', 'liqa-badge--green'],
        'user.unverified' => ['سحب توثيق', 'liqa-badge--red'],
        'user.risk_cleared' => ['اعتماد حساب كسليم', 'liqa-badge--green'],
        'user.risk_reopened' => ['إلغاء اعتماد الحساب', 'liqa-badge--pink'],
        'user.join_restricted' => ['تقييد انضمام عضو', 'liqa-badge--red'],
        'user.join_unrestricted' => ['رفع تقييد الانضمام', 'liqa-badge--green'],
        'user.nominated' => ['ترشيح عضو كمنظّم', 'liqa-badge--sky'],
        'segment.created' => ['إنشاء شريحة', 'liqa-badge--sky'],
        'segment.deleted' => ['حذف شريحة', 'liqa-badge--red'],
        'segment.sent' => ['إشعار لشريحة', 'liqa-badge--pink'],
        'user.rescored_all' => ['فحص الأعضاء', ''],
        'user.password_reset' => ['إعادة تعيين كلمة مرور', 'liqa-badge--pink'],
        'user.deleted' => ['حذف مستخدم', 'liqa-badge--red'],
        'event.deleted' => ['حذف حدث', 'liqa-badge--red'],
        'event.cancelled' => ['إلغاء حدث', 'liqa-badge--red'],
        'report.reviewed' => ['مراجعة بلاغ', 'liqa-badge--sky'],
        'user.permissions' => ['تعديل صلاحيات مدير', 'liqa-badge--pink'],
        'settings.updated' => ['تعديل إعدادات المنصة', 'liqa-badge--sky'],
        'admin.2fa_enabled' => ['تفعيل التحقق بخطوتين', 'liqa-badge--green'],
        'admin.2fa_disabled' => ['إيقاف التحقق بخطوتين', 'liqa-badge--red'],
        'admin.2fa_failed' => ['فشل التحقق بخطوتين', 'liqa-badge--red'],
        'rating.deleted' => ['حذف تقييم', 'liqa-badge--red'],
        'message.deleted' => ['حذف رسالة تواصل', 'liqa-badge--red'],
        'comment.deleted' => ['حذف تعليق', 'liqa-badge--red'],
        'broadcast.sent' => ['إشعار عام', 'liqa-badge--pink'],
        'export.csv' => ['تصدير بيانات', 'liqa-badge--sky'],
        'report.reopened' => ['إعادة فتح بلاغ', 'liqa-badge--sky'],
    ];

    protected $fillable = ['admin_id', 'admin_name', 'action', 'target_type', 'target_id', 'target_label', 'details', 'ip', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action][0] ?? $this->action;
    }

    public function actionClass(): string
    {
        return self::ACTIONS[$this->action][1] ?? '';
    }

    /** تسجيل إجراء إداري */
    public static function record(?User $admin, string $action, ?string $targetType = null, ?int $targetId = null, ?string $label = null, ?string $details = null): void
    {
        static::create([
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->name,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_label' => $label,
            'details' => $details,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
