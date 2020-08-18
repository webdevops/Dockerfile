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
        describe url do
            it "should have running and answering webserver", :retry => 20, :retry_wait => 3 do
                cmd = command("curl --insecure --silent --retry 10 --fail #{url}")
                expect(cmd.stdout).not_to contain('PHP Notice')
                expect(cmd.stdout).not_to contain('Notice')
                expect(cmd.stdout).not_to contain('PHP Warning')
                expect(cmd.stdout).not_to contain('Warning')
                expect(cmd.stdout).not_to contain('Fatal Error')
                expect(cmd.stdout).to contain('2ae62521966cf6d4188acefc943c903e5fc0a25c')
                expect(cmd.exit_status).to eq 0
            end
        end
    end
end

shared_examples 'php::fpm::test::php_sapi_name' do
    [
        'http://localhost/php-test.php?test=php_sapi_name',
        'https://localhost/php-test.php?test=php_sapi_name'
    ].each do |url|
        describe url do
            it "should have running and answering webserver", :retry => 20, :retry_wait => 3 do
                cmd = command("curl --insecure --silent --retry 10 --fail #{url}")
                expect(cmd.stdout).not_to contain('PHP Notice')
                expect(cmd.stdout).not_to contain('Notice')
                expect(cmd.stdout).not_to contain('PHP Warning')
                expect(cmd.stdout).not_to contain('Warning')
                expect(cmd.stdout).not_to contain('Fatal Error')
                expect(cmd.stdout).to contain('fpm-fcgi')
                expect(cmd.exit_status).to eq 0
            end
        end
    end
end


shared_examples 'php::fpm::test::php_ini_scanned_files' do
    [
        'http://localhost/php-test.php?test=php_ini_scanned_files',
        'https://localhost/php-test.php?test=php_ini_scanned_files'
    ].each do |url|
        describe url do
            it "should have running and answering webserver", :retry => 20, :retry_wait => 3 do
                cmd = command("curl --insecure --silent --retry 10 --fail #{url}")
                expect(cmd.stdout).not_to contain('PHP Notice')
                expect(cmd.stdout).not_to contain('Notice')
                expect(cmd.stdout).not_to contain('PHP Warning')
                expect(cmd.stdout).not_to contain('Warning')
                expect(cmd.stdout).not_to contain('Fatal Error')
                expect(cmd.stdout).to contain('-docker.ini')
                expect(cmd.stdout).to contain('-webdevops.ini')
                expect(cmd.exit_status).to eq 0
            end
        end
    end
end

shared_examples 'php5::fpm::test::version' do
    [
        'http://localhost/php-test.php?test=version',
        'https://localhost/php-test.php?test=version'
    ].each do |url|
        describe url do
            it "should have running and answering webserver", :retry => 20, :retry_wait => 3 do
                cmd = command("curl --insecure --silent --retry 10 --fail #{url}")
                expect(cmd.stdout).not_to contain('PHP Notice')
                expect(cmd.stdout).not_to contain('Notice')
                expect(cmd.stdout).not_to contain('PHP Warning')
                expect(cmd.stdout).not_to contain('Warning')
                expect(cmd.stdout).not_to contain('Fatal Error')
                expect(cmd.stdout).to match %r!PHP 5\.[3-9]\.[0-9]{1,2}(-[^\(]*)?!
                expect(cmd.exit_status).to eq 0
            end
        end
    end
end

shared_examples 'php7::fpm::test::version' do
    [
        'http://localhost/php-test.php?test=version',
        'https://localhost/php-test.php?test=version'
    ].each do |url|
        describe url do
            it "should have running and answering webserver", :retry => 20, :retry_wait => 3 do
                cmd = command("curl --insecure --silent --retry 10 --fail #{url}")
                expect(cmd.stdout).not_to contain('PHP Notice')
                expect(cmd.stdout).not_to contain('Notice')
                expect(cmd.stdout).not_to contain('PHP Warning')
                expect(cmd.stdout).not_to contain('Warning')
                expect(cmd.stdout).not_to contain('Fatal Error')
                expect(cmd.stdout).to match %r!PHP (?:7|8)\.[0-9]\.[0-9]{1,2}(-[^\(]*)?!
                expect(cmd.exit_status).to eq 0
            end
        end
    end
end

shared_examples 'php::fpm::test::process_user_id' do
    [
        'http://localhost/php-test.php?test=process_user_id',
        'https://localhost/php-test.php?test=process_user_id'
    ].each do |url|
        describe url do
            it "should have running and answering webserver", :retry => 20, :retry_wait => 3 do
                cmd = command("curl --insecure --silent --retry 10 --fail #{url}")
                expect(cmd.stdout).not_to contain('PHP Notice')
                expect(cmd.stdout).not_to contain('Notice')
                expect(cmd.stdout).not_to contain('PHP Warning')
                expect(cmd.stdout).not_to contain('Warning')
                expect(cmd.stdout).not_to contain('Fatal Error')
                expect(cmd.stdout).to contain('UID:1000#')
                expect(cmd.exit_status).to eq 0
            end
        end
    end
end
