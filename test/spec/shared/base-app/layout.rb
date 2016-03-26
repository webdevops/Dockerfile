shared_examples 'base-app::layout' do
    #########################
    ## Files
    #########################
    [
        "/opt/docker/etc/supervisor.d/dnsmasq.conf",
        "/opt/docker/etc/supervisor.d/postfix.conf",
        "/opt/docker/etc/supervisor.d/ssh.conf",
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
            it { should_not be_executable.by('owner') }
            it { should_not be_executable.by('group') }
            it { should_not be_executable.by('others') }
        end
    end

    #########################
    ## Scripts
    #########################
    [
        "/opt/docker/bin/service.d/dnsmasq.sh",
        "/opt/docker/bin/service.d/postfix.sh",
    ].each do |file|
        describe file("#{file}") do
            # Type check
            it { should be_file }
            it { should be_executable }

            # Owner test
            it { should be_owned_by 'root' }
            it { should be_grouped_into 'root' }

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
end
