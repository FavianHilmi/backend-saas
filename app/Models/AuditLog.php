<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'model',
        'model_id',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}
