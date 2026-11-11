<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\BaseAppChecks;
use Webdevops\Build\ImageTests\Checks\BaseChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\Checks\NginxChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmLocalOnlyChecks;
use Webdevops\Build\ImageTests\Checks\PhpProductionChecks;
use Webdevops\Build\ImageTests\Checks\PhpWebProductionChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/php-nginx
 */
final class PhpNginxTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseChecks;
    use BaseAppChecks;
    use PhpProductionChecks;
    use PhpFpmChecks;
    use PhpFpmLocalOnlyChecks;
    use NginxChecks;
    use PhpWebProductionChecks;
}
