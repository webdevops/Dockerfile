<?php

declare(strict_types=1);

namespace Webdevops\Build\ImageTests\Checks;

use Webdevops\Build\ImageTests\ImageTestCase;

/**
 * The self-signed certificate for *.vm that the web server images create at their build, see docs/images/nginx.md.
 *
 * @phpstan-require-extends ImageTestCase
 */
trait TlsCertificateChecks
{
    /**
     * The certificate in $directory is valid for at least one more year, names *.vm and belongs to the key next to it.
     */
    private function assertTlsCertificateIsValid(string $directory): void
    {
        $certificate = $directory . '/server.crt';
        $this->assertCommandSucceeds('openssl x509 -in ' . $certificate . ' -noout -checkend 31536000');
        self::assertStringContainsString('DNS:*.vm', $this->stdout('openssl x509 -in ' . $certificate . ' -noout -ext subjectAltName'));
        self::assertSame(
            $this->stdout('openssl x509 -in ' . $certificate . ' -noout -pubkey'),
            $this->stdout('openssl pkey -in ' . $directory . '/server.key -pubout'),
            'the key belongs to the certificate',
        );
    }
}
