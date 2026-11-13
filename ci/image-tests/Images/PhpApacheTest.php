<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\ApacheChecks;
use Webdevops\Build\ImageTests\Checks\ApacheEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\BaseAppChecks;
use Webdevops\Build\ImageTests\Checks\BaseChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\Checks\ContainerEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\EntrypointChecks;
use Webdevops\Build\ImageTests\Checks\PhpEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmLocalOnlyChecks;
use Webdevops\Build\ImageTests\Checks\PhpProductionChecks;
use Webdevops\Build\ImageTests\Checks\PhpWebEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpWebProductionChecks;
use Webdevops\Build\ImageTests\Checks\ServiceChecks;
use Webdevops\Build\ImageTests\Checks\WebEnvironmentChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/php-apache
 */
final class PhpApacheTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseChecks;
    use BaseAppChecks;
    use PhpProductionChecks;
    use PhpFpmChecks;
    use PhpFpmLocalOnlyChecks;
    use ApacheChecks;
    use PhpWebProductionChecks;
    use EntrypointChecks;
    use ServiceChecks;
    use ContainerEnvironmentChecks;
    use PhpEnvironmentChecks;
    use PhpFpmEnvironmentChecks;
    use WebEnvironmentChecks;
    use ApacheEnvironmentChecks;
    use PhpWebEnvironmentChecks;

    protected const array SERVICES = ['apache', 'cron', 'php-fpm', 'syslog'];
}
