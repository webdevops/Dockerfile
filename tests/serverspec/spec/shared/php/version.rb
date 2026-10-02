shared_examples 'php::cli::version' do
    describe command('php -v') do
        its(:stdout) { should match %r!PHP #{Regexp.escape(ENV.fetch('DOCKER_TAG').split('-', 2).first)}\.[0-9]+(RC[0-9]|beta[0-9])?(-[^\(]*)? \(cli\)! }

        its(:exit_status) { should eq 0 }
    end
end
