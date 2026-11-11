<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * WEB_DOCUMENT_ROOT and WEB_DOCUMENT_INDEX in the environment container of a web server image, see
 * docs/environment-variables.md#web-server. Fixtures/ has the document root /app/image-test-root.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait WebEnvironmentChecks
{
    public function testDocumentRootAndIndexFromTheEnvironment(): void
    {
        self::assertSame(
            "Document root and index from WEB_DOCUMENT_ROOT and WEB_DOCUMENT_INDEX\n",
            $this->request('http://localhost/', container: $this->environmentContainer()),
        );
    }
}
