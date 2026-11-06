require 'rubygems/version'

shared_examples 'php::cli::version' do
    php_minor = ENV.fetch('DOCKER_TAG').split('-', 2).first
    # Security releases from 2026-10-21: https://www.php.net/
    minimum_version = {
        '8.2' => '8.2.35',
        '8.3' => '8.3.36',
        '8.4' => '8.4.27',
        '8.5' => '8.5.12',
        # TODO PHP 8.6: raise to the first stable 8.6 release once the image uses php:8.6-fpm-*
        '8.6' => '8.6.0RC4',
    }.fetch(php_minor)
    # TODO PHP 8.6: release candidates are only accepted until PHP 8.6.0 is GA
    allow_release_candidate = php_minor == '8.6'
    version_pattern = allow_release_candidate ?
        /\A#{Regexp.escape(php_minor)}\.[0-9]+(RC[0-9]+)?\z/ :
        /\A#{Regexp.escape(php_minor)}\.[0-9]+\z/

    describe command('php -v') do
        its(:stdout) { should match %r!PHP #{Regexp.escape(php_minor)}\.[0-9]+(RC[0-9]|beta[0-9])?(-[^\(]*)? \(cli\)! }

        its(:exit_status) { should eq 0 }
    end

    describe command(%q!php -r 'echo PHP_VERSION;'!) do
        it "runs PHP #{minimum_version} or newer within #{php_minor}" do
            version = subject.stdout.strip
            expect(version).to match(version_pattern)
            expect(Gem::Version.new(version)).to be >= Gem::Version.new(minimum_version)
        end

        its(:exit_status) { should eq 0 }
    end
end
