<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\AdditionalServicesChecks;
use Webdevops\Build\ImageTests\Checks\BaseAppChecks;
use Webdevops\Build\ImageTests\Checks\BaseChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\Checks\ContainerEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\DnsmasqEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\EntrypointChecks;
use Webdevops\Build\ImageTests\Checks\HealthcheckChecks;
use Webdevops\Build\ImageTests\Checks\PhpEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmPublicChecks;
use Webdevops\Build\ImageTests\Checks\PhpProductionChecks;
use Webdevops\Build\ImageTests\Checks\ServiceChecks;
use Webdevops\Build\ImageTests\Checks\SyslogChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/php and webdevops/php-official
 */
final class PhpTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseChecks;
    use BaseAppChecks;
    use PhpProductionChecks;
    use PhpFpmChecks;
    use PhpFpmPublicChecks;
    use EntrypointChecks;
    use ServiceChecks;
    use HealthcheckChecks;
    use ContainerEnvironmentChecks;
    use PhpEnvironmentChecks;
    use PhpFpmEnvironmentChecks;
    use AdditionalServicesChecks;
    use SyslogChecks;
    use DnsmasqEnvironmentChecks;

    protected const array SERVICES = ['cron', 'php-fpm', 'syslog'];
}
