<?php

use Laravel\Ranger\Concerns\HasFilePath;

describe('HasFilePath trait', function () {
    it('can set and get file path', function () {
        $class = new class
        {
            use HasFilePath;
        };

        $result = $class->setFilePath('/path/to/file.php');

        expect($result)->toBe($class);
        expect($class->filePath())->toBe('/path/to/file.php');
    });

    it('returns self for method chaining', function () {
        $class = new class
        {
            use HasFilePath;
        };

        $result = $class->setFilePath('/path/to/file.php');

        expect($result)->toBeInstanceOf(get_class($class));
    });
});
