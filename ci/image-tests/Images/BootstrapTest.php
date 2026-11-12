<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/bootstrap
 */
final class BootstrapTest extends ImageTestCase
{
    use BootstrapChecks;
}
