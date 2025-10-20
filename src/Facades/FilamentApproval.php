<?php

namespace Xplodman\FilamentApproval\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Xplodman\FilamentApproval\FilamentApproval
 */
class FilamentApproval extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Xplodman\FilamentApproval\FilamentApproval::class;
    }
}
