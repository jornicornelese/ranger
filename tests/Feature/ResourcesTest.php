<?php

use App\Http\Resources\UserResource;
use Laravel\Ranger\Collectors\Resources;
use Laravel\Ranger\Components\Resource;

beforeEach(function () {
    $this->collector = app(Resources::class);
});

describe('resource collection', function () {
    it('collects resources from the application', function () {
        $resources = $this->collector->collect();

        expect($resources)->not->toBeEmpty();
    });

    it('finds the UserResource', function () {
        $resources = $this->collector->collect();
        $userResource = $resources->first(fn (Resource $r) => $r->name === UserResource::class);

        expect($userResource)->not->toBeNull();
        expect($userResource)->toBeInstanceOf(Resource::class);
    });

    it('does not collect non-JsonResource classes', function () {
        $resources = $this->collector->collect();

        $names = $resources->map(fn (Resource $r) => $r->name)->all();

        expect($names)->not->toContain('App\\Http\\Resources\\StatsData');
    });
});

describe('resource fields', function () {
    it('captures toArray fields', function () {
        $resources = $this->collector->collect();
        $userResource = $resources->first(fn (Resource $r) => $r->name === UserResource::class);

        $fields = $userResource->getFields();

        expect($fields)->toHaveKey('id');
        expect($fields)->toHaveKey('name');
        expect($fields)->toHaveKey('email');
    });
});

describe('resource file path', function () {
    it('sets file path on resource components', function () {
        $resources = $this->collector->collect();
        $userResource = $resources->first(fn (Resource $r) => $r->name === UserResource::class);

        expect($userResource->filePath())->toContain('UserResource.php');
    });
});
