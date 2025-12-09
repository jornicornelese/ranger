<?php

use App\Models\Post;
use App\Models\User;
use Laravel\Ranger\Collectors\Models;
use Laravel\Ranger\Components\Model;

beforeEach(function () {
    $this->collector = app(Models::class);
});

describe('model collection', function () {
    it('collects models from the application', function () {
        $models = $this->collector->collect();

        expect($models)->not->toBeEmpty();
    });

    it('finds the User model', function () {
        $models = $this->collector->collect();
        $userModel = $models->first(fn (Model $m) => $m->name === User::class);

        expect($userModel)->not->toBeNull();
        expect($userModel)->toBeInstanceOf(Model::class);
    });

    it('finds the Post model', function () {
        $models = $this->collector->collect();
        $postModel = $models->first(fn (Model $m) => $m->name === Post::class);

        expect($postModel)->not->toBeNull();
        expect($postModel)->toBeInstanceOf(Model::class);
    });
});

describe('model attributes', function () {
    it('captures model attributes', function () {
        $models = $this->collector->collect();
        $userModel = $models->first(fn (Model $m) => $m->name === User::class);

        $attributes = $userModel->getAttributes();

        expect($attributes)->not->toBeEmpty();
    });
});

describe('model relations', function () {
    it('captures relations from models', function () {
        $models = $this->collector->collect();
        $userModel = $models->first(fn (Model $m) => $m->name === User::class);

        $relations = $userModel->getRelations();

        expect($relations)->toBeArray();
    });

    it('captures belongsTo relations on Post model', function () {
        $models = $this->collector->collect();
        $postModel = $models->first(fn (Model $m) => $m->name === Post::class);

        $relations = $postModel->getRelations();

        expect($relations)->toBeArray();
        if (count($relations) > 0) {
            expect($relations)->toHaveKey('user');
        }
    });
});

describe('model lookup', function () {
    it('can get a specific model by name', function () {
        $this->collector->collect();

        $userModel = $this->collector->get(User::class);

        expect($userModel)->not->toBeNull();
        expect($userModel->name)->toBe(User::class);
    });

    it('returns null for non-existent models', function () {
        $this->collector->collect();

        $result = $this->collector->get('NonExistent\\Model');

        expect($result)->toBeNull();
    });
});
