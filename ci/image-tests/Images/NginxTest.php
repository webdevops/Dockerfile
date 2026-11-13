<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\BaseChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\Checks\CliWithoutApplicationUserChecks;
use Webdevops\Build\ImageTests\Checks\ContainerEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\EntrypointChecks;
use Webdevops\Build\ImageTests\Checks\NginxChecks;
use Webdevops\Build\ImageTests\Checks\NginxEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\ServiceChecks;
use Webdevops\Build\ImageTests\Checks\WebEnvironmentChecks;
use Webdevops\Build\ImageTests\Checks\WithoutAppDirectoryChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/nginx
 */
final class NginxTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseChecks;
    use NginxChecks;
    use EntrypointChecks;
    use ServiceChecks;
    use ContainerEnvironmentChecks;
    use CliWithoutApplicationUserChecks;
    use WebEnvironmentChecks;
    use NginxEnvironmentChecks;
    use WithoutAppDirectoryChecks;

    protected const array SERVICES = ['nginx'];
}
