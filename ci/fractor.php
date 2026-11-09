<?php

declare(strict_types=1);

use a9f\Fractor\Configuration\FractorConfiguration;
use PLUS\GrumPHPConfig\FractorSettings;

return FractorConfiguration::configure()
    ->withPaths(array_filter(explode("\n", (string)shell_exec("git ls-files | xargs ls -d 2>/dev/null"))))
    ->withSets([
        ...FractorSettings::sets(true),
    ])
    ->withRules([
        ...FractorSettings::rules(),
    ])
    ->withOptions([
        ...FractorSettings::options(),
    ]);
