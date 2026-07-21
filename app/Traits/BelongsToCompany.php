<?php

namespace App\Traits;

use App\models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // function ini otomatis assign UUID & company_id saat create record
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            if (Auth::check() && empty($model->company_id)) {
                $model->company_id = Auth::user()->company_id;
            }
        });

        // query hanya ke company dari user yang sedang login
        static::addGlobalScope('tenant_isolation', function (Builder $builder) {
            if (Auth::check() && Auth::user()->company_id) {
                $table = $builder->getModel()->getTable();
                $builder->where("{$table}.company_id", Auth::user()->company_id);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
