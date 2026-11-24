<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** لقطة يومية لمؤشر صحة المجتمع لعرض الاتجاه */
class HealthSnapshot extends Model
{
    protected $fillable = ['day', 'score', 'parts'];

    protected function casts(): array
    {
        return ['day' => 'date', 'parts' => 'array'];
    }
}
