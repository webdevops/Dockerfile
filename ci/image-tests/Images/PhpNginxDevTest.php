<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\BaseAppChecks;
use Webdevops\Build\ImageTests\Checks\BaseChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\Checks\ContainerEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\EntrypointChecks;
use Webdevops\Build\ImageTests\Checks\NginxChecks;
use Webdevops\Build\ImageTests\Checks\NginxEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpDevelopmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpDevelopmentEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmPublicChecks;
use Webdevops\Build\ImageTests\Checks\PhpWebDevelopmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpWebEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\PostfixChecks;
use Webdevops\Build\ImageTests\Checks\ServiceChecks;
use Webdevops\Build\ImageTests\Checks\WebEnvironmentChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/php-nginx-dev
 */
final class PhpNginxDevTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseChecks;
    use BaseAppChecks;
    use PhpDevelopmentChecks;
    use PhpFpmChecks;
    use PhpFpmPublicChecks;
    use NginxChecks;
    use PhpWebDevelopmentChecks;
    use EntrypointChecks;
    use ServiceChecks;
    use ContainerEnvironmentChecks;
    use PhpEnvironmentChecks;
    use PhpFpmEnvironmentChecks;
    use PhpDevelopmentEnvironmentChecks;
    use PostfixChecks;
    use WebEnvironmentChecks;
    use NginxEnvironmentChecks;
    use PhpWebEnvironmentChecks;

    protected const array SERVICES = ['cron', 'nginx', 'php-fpm', 'postfix', 'ssh', 'syslog'];
}
