shared_examples 'ansible::toolchain' do

    ansibleList = [
        $testConfiguration[:ansiblePath] + "/ansible",
        $testConfiguration[:ansiblePath] + "/ansible-playbook",
        $testConfiguration[:ansiblePath] + "/ansible-galaxy",
        $testConfiguration[:ansiblePath] + "/ansible-pull",
        $testConfiguration[:ansiblePath] + "/ansible-doc",
        $testConfiguration[:ansiblePath] + "/ansible-vault",
    ].each do |file|
        describe file("#{file}") do
            # Type check
            it { should be_file }

            # Owner test
            it { should be_owned_by 'root' }
            it { should be_grouped_into 'root' }

            # Read test
            it { should be_readable.by('owner') }
            it { should be_readable.by('group') }
            it { should_not be_readable.by('others') }

            # Write test
            it { should be_writable.by('owner') }
            it { should_not be_writable.by('group') }
            it { should_not be_writable.by('others') }

            # Exectuable test
            it { should be_executable.by('owner') }
            it { should be_executable.by('group') }
            it { should_not be_executable.by('others') }
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

shared_examples 'ansible::toolchain::public' do

    ansibleList = [
        $testConfiguration[:ansiblePath] + "/ansible",
        $testConfiguration[:ansiblePath] + "/ansible-playbook",
        $testConfiguration[:ansiblePath] + "/ansible-galaxy",
        $testConfiguration[:ansiblePath] + "/ansible-pull",
        $testConfiguration[:ansiblePath] + "/ansible-doc",
        $testConfiguration[:ansiblePath] + "/ansible-vault",
    ].each do |file|
        describe file("#{file}") do
            # Type check
            it { should be_file }

            # Owner test
            it { should be_owned_by 'root' }
            it { should be_grouped_into 'root' }

            # Read test
            it { should be_readable.by('owner') }
            it { should be_readable.by('group') }
            it { should be_readable.by('others') }

            # Write test
            it { should be_writable.by('owner') }
            it { should_not be_writable.by('group') }
            it { should_not be_writable.by('others') }

            # Exectuable test
            it { should be_executable.by('owner') }
            it { should be_executable.by('group') }
            it { should be_executable.by('others') }
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
