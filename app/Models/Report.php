<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public const REASONS = [
        'abuse' => 'سلوك مسيء أو مضايقة',
        'fake' => 'حساب مزيّف أو انتحال',
        'spam' => 'إعلان أو محتوى مزعج',
        'other' => 'سبب آخر',
    ];

    protected $fillable = ['reporter_id', 'target_type', 'target_id', 'reason', 'details', 'status'];

    /** الهدف المُبلَّغ عنه (مستخدم أو حدث) أو null لو حُذف */
    public function target(): User|Event|null
    {
        return $this->target_type === 'user' ? User::find($this->target_id) : Event::with('host')->find($this->target_id);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
