<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends BaseModel
{
    protected $table = 'leads';

    protected $guarded = [];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
