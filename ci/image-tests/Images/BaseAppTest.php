<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\BaseAppChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/base-app
 */
final class BaseAppTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseAppChecks;
}
