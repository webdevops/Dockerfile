shared_examples 'bootstrap::toolchain' do

    describe command('python --version') do
        its(:exit_status) { should eq 0 }
    end

    describe command('easy_install --version') do
        its(:exit_status) { should eq 0 }
    end

    describe command('pip --help') do
        its(:exit_status) { should eq 0 }
    end

end
