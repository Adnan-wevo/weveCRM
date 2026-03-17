<?php

namespace Modules\CRM\Models;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Call extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'crm_calls';

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'user_id',
        'direction',
        'duration',
        'notes',
        'called_at',
    ];

    protected function casts(): array
    {
        return [
            'called_at' => 'datetime',
            'duration' => 'integer',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
