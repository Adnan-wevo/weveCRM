<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends BaseModel
{
    protected $table = 'contacts';

    protected $guarded = [];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
