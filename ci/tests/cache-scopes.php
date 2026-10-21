<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$workflow = Yaml::parseFile(__DIR__ . '/../../.github/workflows/build.yaml');
$scopes = [];
$variants = [];
$checks = 0;

foreach ($workflow['jobs'] as $job) {
    $builds = array_filter($job['steps'], fn (array $step): bool =>
        str_starts_with($step['uses'] ?? '', 'docker/build-push-action@')
    );
    if (!$builds) {
        continue;
    }

    $image = str_replace(' (${{ matrix.arch }})', '', $job['name']);
    foreach ($job['strategy']['matrix']['include'] as $matrix) {
        $scope = $image . '-' . $matrix['arch'];
        if (isset($scopes[$scope])) {
            throw new RuntimeException('Cache scope reused by another image/architecture: ' . $scope);
        }
        $scopes[$scope] = true;

        foreach ($builds as $step) {
            // The exact image/tag + architecture contract also excludes commit,
            // branch and run identifiers, keeping scopes stable across builds.
            foreach (['cache-from' => '', 'cache-to' => ',mode=max'] as $key => $suffix) {
                $actual = str_replace('${{ matrix.arch }}', $matrix['arch'], $step['with'][$key] ?? '');
                if ($actual !== 'type=gha,scope=' . $scope . $suffix) {
                    throw new RuntimeException($image . ': ' . $step['name'] . ' has incorrect ' . $key . ': ' . $actual);
                }
                $checks++;
            }
            $variants[$step['name']] = true;
        }
    }
}

foreach (['Build (load locally)', 'Build (load locally, from parent artifact)', 'Export image (OCI layout)'] as $variant) {
    if (!isset($variants[$variant])) {
        throw new RuntimeException('Missing BuildKit variant coverage: ' . $variant);
    }
}

printf("Passed %d cache checks across %d unique image/architecture scopes.\n", $checks, count($scopes));
