<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'customers';
    protected $guarded = [];

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'customer_id');
    }
}
