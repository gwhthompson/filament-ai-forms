<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    /** @use HasFactory<TestModelFactory> */
    use HasFactory;

    protected $table = 'test_models';

    protected $guarded = [];

    protected static function newFactory(): TestModelFactory
    {
        return TestModelFactory::new();
    }
}
