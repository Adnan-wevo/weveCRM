<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Example tenant-scoped model that works in both single-db and multi-db tenancy.
 *
 * - Single-DB: BelongsToTenant (from BaseModel) scopes queries by tenant_id.
 * - Multi-DB:  Each tenant has its own database; tenant_id is set automatically
 *              as extra safety but the real isolation comes from the DB connection.
 *
 * Migration lives in database/migrations/tenant/.
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $name
 * @property string|null $description
 * @property int $price
 * @property int $stock
 * @property \Carbon\Carbon|null $deleted_at
 */
class TestProduct extends BaseModel
{
    /** @use HasFactory<\Database\Factories\TestProductFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
        ];
    }
}
