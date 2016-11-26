shared_examples 'hhvm::cli::version' do
    describe command('php -v') do
        its(:stdout) { should match %r!HipHop VM [0-9]+.[0-9]+.[0-9]+ \(rel\)! }

        its(:exit_status) { should eq 0 }
    end
end
