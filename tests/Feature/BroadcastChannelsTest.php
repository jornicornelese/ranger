<?php

use Illuminate\Support\Facades\Broadcast;
use Laravel\Ranger\Collectors\BroadcastChannels;
use Laravel\Ranger\Components\BroadcastChannel;

beforeEach(function () {
    Broadcast::channel('users.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
    Broadcast::channel('posts', fn () => true);
    Broadcast::channel('team.{team}', fn ($user, $team) => $user->belongsToTeam($team));

    $this->collector = app(BroadcastChannels::class);
});

describe('broadcast channel collection', function () {
    it('collects broadcast channels from the application', function () {
        $channels = $this->collector->collect();

        expect($channels)->not->toBeEmpty();
    });

    it('creates BroadcastChannel components', function () {
        $channels = $this->collector->collect();

        $channels->each(function ($channel) {
            expect($channel)->toBeInstanceOf(BroadcastChannel::class);
        });
    });

    it('captures channel names', function () {
        $channels = $this->collector->collect();

        $names = $channels->pluck('name')->toArray();

        expect($names)->toContain('users.{id}');
        expect($names)->toContain('posts');
        expect($names)->toContain('team.{team}');
    });

    it('captures channel resolver', function () {
        $channels = $this->collector->collect();
        $usersChannel = $channels->first(fn (BroadcastChannel $c) => $c->name === 'users.{id}');

        expect($usersChannel->resolvesTo)->not->toBeNull();
    });
});
