<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\AdditionalServicesChecks;
use Webdevops\Build\ImageTests\Checks\BaseChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\Checks\CliWithoutApplicationUserChecks;
use Webdevops\Build\ImageTests\Checks\ContainerEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\DnsmasqEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\EntrypointChecks;
use Webdevops\Build\ImageTests\Checks\ServiceChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/base
 */
final class BaseTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseChecks;
    use AdditionalServicesChecks;
    use EntrypointChecks;
    use ServiceChecks;
    use ContainerEnvironmentChecks;
    use CliWithoutApplicationUserChecks;
    use DnsmasqEnvironmentChecks;
}
