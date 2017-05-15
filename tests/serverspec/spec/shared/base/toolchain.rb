shared_examples 'base::toolchain' do
    describe command('go-replace --version') do
        its(:exit_status) { should eq 0 }
    end
end
