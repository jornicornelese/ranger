<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;

class StatsData implements Arrayable
{
    public function toArray(): array
    {
        return [
            'total_users' => 100,
            'active_users' => 50,
        ];
    }
}
