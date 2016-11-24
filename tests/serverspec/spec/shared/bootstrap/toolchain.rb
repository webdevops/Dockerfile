shared_examples 'bootstrap::toolchain' do

    describe command('python --version') do
        its(:exit_status) { should eq 0 }
    end

    describe command('easy_install --version'), :if => ['debian', 'ubuntu', 'redhat'].include?(os[:family]) do
        its(:exit_status) { should eq 0 }
    end

    describe command('easy_install-2.7 --version'), :if => ['alpine'].include?(os[:family]) do
        its(:exit_status) { should eq 0 }
    end

    describe command('pip --help') do
        its(:exit_status) { should eq 0 }
    end

    describe command('rpl --version') do
        its(:exit_status) { should eq 0 }
    end
end
