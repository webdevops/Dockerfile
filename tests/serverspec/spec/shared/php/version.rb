shared_examples 'php::cli::version' do
    describe command('php -v') do
        its(:stdout) { should match %r!PHP 8\.(?:[1-9][0-9]*)\.[0-9]+(RC[0-9]|beta[0-9])?(-[^\(]*)? \(cli\)! }

        its(:exit_status) { should eq 0 }
    end
end
