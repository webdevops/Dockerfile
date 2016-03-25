shared_examples 'bootstrap::ansible' do

    it "should be ansible installed" do
        if os[:family] == 'redhat'
            expect(file("/usr/bin/ansible")).to be_executable
            expect(file("/usr/bin/ansible-playbook")).to be_executable
        elsif ['debian', 'ubuntu'].include?(os[:family])
            expect(file("/usr/local/bin/ansible")).to be_executable
            expect(file("/usr/local/bin/ansible-playbook")).to be_executable
        end
    end

    describe command('ansible --version') do
        its(:stdout) { should match $packageVersions[:ansible] }

        its(:exit_status) { should eq 0 }
    end

    describe command('ansible-playbook --version') do
        its(:stdout) { should match $packageVersions[:ansiblePlaybook] }

        its(:exit_status) { should eq 0 }
    end
end
