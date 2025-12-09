<?php

use Illuminate\Support\Collection;
use Laravel\Ranger\Collectors\Collector;

describe('Collector base class', function () {
    it('caches the collection after first call', function () {
        $collector = new class extends Collector
        {
            public int $collectCalls = 0;

            public function collect(): Collection
            {
                $this->collectCalls++;

                return collect(['item1', 'item2']);
            }
        };

        $first = $collector->getCollection();
        $second = $collector->getCollection();

        expect($collector->collectCalls)->toBe(1);
        expect($first)->toBe($second);
    });

    it('runs callbacks on each item', function () {
        $collector = new class extends Collector
        {
            public function collect(): Collection
            {
                return collect(['a', 'b', 'c']);
            }
        };

        $results = [];

        $callbacks = [
            function ($item) use (&$results) {
                $results[] = strtoupper($item);
            },
        ];

        $collector->run($callbacks);

        expect($results)->toBe(['A', 'B', 'C']);
    });

    it('runs multiple callbacks on each item', function () {
        $collector = new class extends Collector
        {
            public function collect(): Collection
            {
                return collect(['x', 'y']);
            }
        };

        $results1 = [];
        $results2 = [];

        $callbacks = [
            function ($item) use (&$results1) {
                $results1[] = $item.'1';
            },
            function ($item) use (&$results2) {
                $results2[] = $item.'2';
            },
        ];

        $collector->run($callbacks);

        expect($results1)->toBe(['x1', 'y1']);
        expect($results2)->toBe(['x2', 'y2']);
    });

    it('runs collection callbacks with entire collection', function () {
        $collector = new class extends Collector
        {
            public function collect(): Collection
            {
                return collect(['a', 'b', 'c']);
            }
        };

        /** @var Collection $receivedCollection */
        $receivedCollection = null;

        $callbacks = [
            function (Collection $collection) use (&$receivedCollection) {
                $receivedCollection = $collection;
            },
        ];

        $collector->runOnCollection($callbacks);

        expect($receivedCollection)->toBeInstanceOf(Collection::class);
        expect($receivedCollection->toArray())->toBe(['a', 'b', 'c']);
    });

    it('runs multiple collection callbacks', function () {
        $collector = new class extends Collector
        {
            public function collect(): Collection
            {
                return collect([1, 2, 3]);
            }
        };

        $sum = 0;
        $count = 0;

        $callbacks = [
            function (Collection $collection) use (&$sum) {
                $sum = $collection->sum();
            },
            function (Collection $collection) use (&$count) {
                $count = $collection->count();
            },
        ];

        $collector->runOnCollection($callbacks);

        expect($sum)->toBe(6);
        expect($count)->toBe(3);
    });
});
