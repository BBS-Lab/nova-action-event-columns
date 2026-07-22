<?php

declare(strict_types=1);

arch('no debugging helpers are left behind')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'dexit'])
    ->not->toBeUsed();

arch('the whole package declares strict types')
    ->expect('BBSLab\NovaActionEventColumns')
    ->toUseStrictTypes();

arch('no class in the package is declared final')
    ->expect('BBSLab\NovaActionEventColumns')
    ->not->toBeFinal();
