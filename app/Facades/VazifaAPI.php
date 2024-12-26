<?php

namespace App\Facades;

use App\Services\VazifaService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool checkPremiumAccess(string $email)
 */
class VazifaAPI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\VazifaService::class;
    }
}
