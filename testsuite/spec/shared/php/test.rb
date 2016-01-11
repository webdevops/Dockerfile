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

shared_examples 'php::fpm::test::sha1' do
    describe command('wget -O- http://localhost/php-test.php?test=sha1') do
        its(:stdout) { should_not contain('PHP Notice') }
        its(:stdout) { should_not contain('Notice') }
        its(:stdout) { should_not contain('PHP Warning') }
        its(:stderr) { should_not contain('PHP Notice') }
        its(:stderr) { should_not contain('Notice') }
        its(:stderr) { should_not contain('PHP Warning') }

        its(:stdout) { should     contain('2ae62521966cf6d4188acefc943c903e5fc0a25c') }
    end
end
