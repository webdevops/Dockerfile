<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Images;

use Webdevops\Build\ImageTests\Checks\BaseChecks;
use Webdevops\Build\ImageTests\Checks\BootstrapChecks;
use Webdevops\Build\ImageTests\Checks\NginxChecks;
use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * webdevops/nginx
 */
final class NginxTest extends ImageTestCase
{
    use BootstrapChecks;
    use BaseChecks;
    use NginxChecks;
}
