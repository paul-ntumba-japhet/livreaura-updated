<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

abstract class CatalogueRecord extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;
}
