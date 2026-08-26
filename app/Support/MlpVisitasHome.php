<?php

namespace App\Support;

use App\Filament\Resources\ClienteResource;

class MlpVisitasHome
{
    public static function url(): string
    {
        return ClienteResource::getUrl();
    }
}
