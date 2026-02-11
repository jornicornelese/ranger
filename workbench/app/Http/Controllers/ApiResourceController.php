<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatsData;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ApiResourceController extends Controller
{
    public function resource(User $user): UserResource
    {
        return UserResource::make($user);
    }

    public function collection(): AnonymousResourceCollection
    {
        return UserResource::collection(User::all());
    }

    public function paginated(): AnonymousResourceCollection
    {
        return UserResource::collection(User::paginate());
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
