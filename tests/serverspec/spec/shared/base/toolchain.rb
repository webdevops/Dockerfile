shared_examples 'base::toolchain' do
    describe command('rpl --version') do
        its(:exit_status) { should eq 0 }
    end
end
