<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Kelurahan;
use App\Support\ActiveKelurahan;

trait ResolvesActiveKelurahan
{
    protected function activeKelurahan(): ?Kelurahan
    {
        return app(ActiveKelurahan::class)->resolve();
    }
}
