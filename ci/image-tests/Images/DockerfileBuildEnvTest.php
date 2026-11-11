<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\BaseAppChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\Checks\BuildEnvChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/dockerfile-build-env
 */
final class DockerfileBuildEnvTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseAppChecks;
    use BuildEnvChecks;
}
