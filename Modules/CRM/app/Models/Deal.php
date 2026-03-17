<?php

namespace Modules\CRM\Models;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRM\Database\Factories\DealFactory;

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

    protected static function newFactory(): DealFactory
    {
        return DealFactory::new();
    }

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }
}
