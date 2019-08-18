shared_examples 'php5::cli::version' do
    describe command('php -v') do
        its(:stdout) { should match %r!PHP 5\.[3-9]\.[0-9]{1,2}(-[^\(]*)? \(cli\)! }

        its(:exit_status) { should eq 0 }
    end
end

shared_examples 'php7::cli::version' do
    describe command('php -v') do
        its(:stdout) { should match %r!PHP 7\.[0-9]\.[0-9]{1,2}(RC[0-9]|beta[0-9])?(-[^\(]*)? \(cli\)! }

        its(:exit_status) { should eq 0 }
    end
end
