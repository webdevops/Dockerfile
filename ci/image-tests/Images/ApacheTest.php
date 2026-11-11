<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\ApacheChecks;
use Webdevops\Build\ImageTests\Checks\ApacheEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\BaseChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\Checks\CliWithoutApplicationUserChecks;
use Webdevops\Build\ImageTests\Checks\ContainerEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\EntrypointChecks;
use Webdevops\Build\ImageTests\Checks\ServiceChecks;
use Webdevops\Build\ImageTests\Checks\WebEnvironmentChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/apache
 */
final class ApacheTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseChecks;
    use ApacheChecks;
    use EntrypointChecks;
    use ServiceChecks;
    use ContainerEnvironmentChecks;
    use CliWithoutApplicationUserChecks;
    use WebEnvironmentChecks;
    use ApacheEnvironmentChecks;

    protected const array SERVICES = ['apache'];
}
