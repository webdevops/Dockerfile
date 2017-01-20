shared_examples 'php::cli::test::sha1' do
    describe command('php -r \'echo sha1("webdevops");\'') do
        its(:stdout) { should_not contain('PHP Notice') }
        its(:stdout) { should_not contain('Notice') }
        its(:stdout) { should_not contain('PHP Warning') }
        its(:stderr) { should_not contain('PHP Notice') }
        its(:stderr) { should_not contain('Notice') }
        its(:stderr) { should_not contain('PHP Warning') }

        its(:stdout) { should     contain('2ae62521966cf6d4188acefc943c903e5fc0a25c') }

        its(:exit_status) { should eq 0 }
    end
end

shared_examples 'php::cli::test::php_ini_scanned_files' do
    describe command('php -r "echo php_ini_scanned_files();"') do
        its(:stdout) { should contain('-docker.ini') }
        its(:stdout) { should contain('-webdevops.ini') }

        its(:exit_status) { should eq 0 }
    end
end

shared_examples 'php::cli::test::php_sapi_name' do
    describe command('php -r "echo php_sapi_name();"') do
        its(:stdout) { should contain('cli') }

        its(:exit_status) { should eq 0 }
    end
end

shared_examples 'php::fpm::test::sha1' do
    [
        'http://localhost/php-test.php?test=sha1',
        'https://localhost/php-test.php?test=sha1'
    ].each do |url|
        describe command("curl --insecure --silent --retry 10 --fail #{url}") do
            its(:stdout) { should_not contain('PHP Notice') }
            its(:stdout) { should_not contain('Notice') }
            its(:stdout) { should_not contain('PHP Warning') }
            its(:stderr) { should_not contain('PHP Notice') }
            its(:stderr) { should_not contain('Notice') }
            its(:stderr) { should_not contain('PHP Warning') }

            its(:stdout) { should     contain('2ae62521966cf6d4188acefc943c903e5fc0a25c') }

            its(:exit_status) { should eq 0 }
        end
    end
end

shared_examples 'php::fpm::test::php_sapi_name' do
    [
        'http://localhost/php-test.php?test=php_sapi_name',
        'https://localhost/php-test.php?test=php_sapi_name'
    ].each do |url|
        describe command("curl --insecure --silent --retry 10 --fail #{url}") do
            its(:stdout) { should_not contain('PHP Notice') }
            its(:stdout) { should_not contain('Notice') }
            its(:stdout) { should_not contain('PHP Warning') }
            its(:stderr) { should_not contain('PHP Notice') }
            its(:stderr) { should_not contain('Notice') }
            its(:stderr) { should_not contain('PHP Warning') }

            its(:stdout) { should     contain('fpm-fcgi') }

            its(:exit_status) { should eq 0 }
        end
    end
end


shared_examples 'php::fpm::test::php_ini_scanned_files' do
    [
        'http://localhost/php-test.php?test=php_ini_scanned_files',
        'https://localhost/php-test.php?test=php_ini_scanned_files'
    ].each do |url|
        describe command("curl --insecure --silent --retry 10 --fail #{url}") do
            its(:stdout) { should_not contain('PHP Notice') }
            its(:stdout) { should_not contain('Notice') }
            its(:stdout) { should_not contain('PHP Warning') }
            its(:stderr) { should_not contain('PHP Notice') }
            its(:stderr) { should_not contain('Notice') }
            its(:stderr) { should_not contain('PHP Warning') }
            its(:stdout) { should contain('-docker.ini') }
            its(:stdout) { should contain('-webdevops.ini') }

            its(:exit_status) { should eq 0 }
        end
    end
end

shared_examples 'php5::fpm::test::version' do
    [
        'http://localhost/php-test.php?test=version',
        'https://localhost/php-test.php?test=version'
    ].each do |url|
        describe command("curl --insecure --silent --retry 10 --fail #{url}") do
            its(:stdout) { should_not contain('PHP Notice') }
            its(:stdout) { should_not contain('Notice') }
            its(:stdout) { should_not contain('PHP Warning') }
            its(:stderr) { should_not contain('PHP Notice') }
            its(:stderr) { should_not contain('Notice') }
            its(:stderr) { should_not contain('PHP Warning') }

            its(:stdout) { should match %r!PHP 5\.[3-9]\.[0-9]{1,2}(-[^\(]*)?! }

            its(:exit_status) { should eq 0 }
        end
    end
end

shared_examples 'php7::fpm::test::version' do
    [
        'http://localhost/php-test.php?test=version',
        'https://localhost/php-test.php?test=version'
    ].each do |url|
        describe command("curl --insecure --silent --retry 10 --fail #{url}") do
            its(:stdout) { should_not contain('PHP Notice') }
            its(:stdout) { should_not contain('Notice') }
            its(:stdout) { should_not contain('PHP Warning') }
            its(:stderr) { should_not contain('PHP Notice') }
            its(:stderr) { should_not contain('Notice') }
            its(:stderr) { should_not contain('PHP Warning') }

            its(:stdout) { should match %r!PHP 7\.[0-9]\.[0-9]{1,2}(-[^\(]*)?! }

            its(:exit_status) { should eq 0 }
        end
    end
end

shared_examples 'php::fpm::test::process_user_id' do
    [
        'http://localhost/php-test.php?test=process_user_id',
        'https://localhost/php-test.php?test=process_user_id'
    ].each do |url|
        describe command("curl --insecure --silent --retry 10 --fail #{url}") do
            its(:stdout) { should_not contain('PHP Notice') }
            its(:stdout) { should_not contain('Notice') }
            its(:stdout) { should_not contain('PHP Warning') }
            its(:stderr) { should_not contain('PHP Notice') }
            its(:stderr) { should_not contain('Notice') }
            its(:stderr) { should_not contain('PHP Warning') }

            its(:stdout) { should contain('UID:1000#') }

            its(:exit_status) { should eq 0 }
        end
    end
end
