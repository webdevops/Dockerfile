<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\ApacheChecks;
use Webdevops\Build\ImageTests\Checks\BaseChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/apache
 */
final class ApacheTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseChecks;
    use ApacheChecks;
}
