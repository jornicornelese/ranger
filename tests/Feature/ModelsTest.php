<?php

use App\Models\Post;
use App\Models\User;
use Laravel\Ranger\Collectors\Models;
use Laravel\Ranger\Components\Model;
use Laravel\Surveyor\Types\ArrayType;
use Laravel\Surveyor\Types\ClassType;

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

    it('resolves belongsTo relation to a nullable related model class', function () {
        $models = $this->collector->collect();
        $postModel = $models->first(fn (Model $m) => $m->name === Post::class);

        $relations = $postModel->getRelations();

        expect($relations)->toHaveKey('user');
        expect($relations['user'])->toBeInstanceOf(ClassType::class);
        expect($relations['user']->value)->toBe(User::class);
        expect($relations['user']->isNullable())->toBeTrue();
    });

    it('resolves hasMany relation to an array of the related model', function () {
        $models = $this->collector->collect();
        $userModel = $models->first(fn (Model $m) => $m->name === User::class);

        $relations = $userModel->getRelations();

        expect($relations)->toHaveKey('posts');
        expect($relations['posts'])->toBeInstanceOf(ArrayType::class);
        expect($relations['posts']->value[0])->toBeInstanceOf(ClassType::class);
        expect($relations['posts']->value[0]->value)->toBe(Post::class);
    });

    it('resolves hasOne relation to a nullable related model class', function () {
        $models = $this->collector->collect();
        $userModel = $models->first(fn (Model $m) => $m->name === User::class);

        $relations = $userModel->getRelations();

        expect($relations)->toHaveKey('post');
        expect($relations['post'])->toBeInstanceOf(ClassType::class);
        expect($relations['post']->value)->toBe(Post::class);
        expect($relations['post']->isNullable())->toBeTrue();
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
