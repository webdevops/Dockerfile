<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\BaseAppChecks;
use Webdevops\Build\ImageTests\Checks\BaseChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\Checks\PhpDevelopmentChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmChecks;
use Webdevops\Build\ImageTests\Checks\PhpFpmPublicChecks;
use Webdevops\Build\ImageTests\Checks\PhpToolsChecks;
use Webdevops\Build\ImageTests\Checks\SshChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/php-dev
 */
final class PhpDevTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseChecks;
    use BaseAppChecks;
    use PhpDevelopmentChecks;
    use PhpFpmChecks;
    use PhpFpmPublicChecks;
    use PhpToolsChecks;
    use SshChecks;
}
