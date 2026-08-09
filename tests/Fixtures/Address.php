<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'customer_addresses';
    protected $guarded = [];
}
