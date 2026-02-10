<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatsData;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Routing\Controller;

class ApiResourceController extends Controller
{
    public function resource(User $user): UserResource
    {
        return UserResource::make($user);
    }

    public function arrayable(): StatsData
    {
        return new StatsData;
    }

    public function plainArray(): array
    {
        return ['status' => 'ok'];
    }
}
