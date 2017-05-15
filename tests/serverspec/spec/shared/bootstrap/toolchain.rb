shared_examples 'bootstrap::toolchain' do
    describe command('go-replace --version') do
        its(:stdout) { should match %r!go-replace[\s]+(version)?[\s]*[0-9]+\.[0-9]+\.[0-9]+! }

        its(:exit_status) { should eq 0 }
    end
end
