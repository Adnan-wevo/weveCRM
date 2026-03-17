<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'crm_companies';

    protected $fillable = [
        'name',
        'industry',
        'website',
        'phone',
        'address',
        'owner_id',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'address' => 'array',
        ];
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'company_id', 'id');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class, 'company_id', 'id');
    }
}
