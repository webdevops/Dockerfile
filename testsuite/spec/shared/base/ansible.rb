shared_examples 'base::ansible' do
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
        its(:stdout) { should match %r!ansible 2.0.[0-9]+! }

        its(:exit_status) { should eq 0 }
    end
end
