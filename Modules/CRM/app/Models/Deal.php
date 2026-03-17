<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'crm_deals';

    protected $fillable = [
        'title',
        'company_id',
        'contact_id',
        'value',
        'currency',
        'stage',
        'status',
        'owner_id',
        'closed_at',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }
}
