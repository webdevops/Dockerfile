shared_examples 'bootstrap::layout' do
    #########################
    ## Directories
    #########################
    [
        "/usr",
        "/usr/local",
        "/usr/local/bin",
    ].each do |file|
        describe file("#{file}") do
            # Type check
            it { should be_directory }

            # Owner test
            it { should be_owned_by 'root' }
            it { should be_grouped_into 'root' }

            # Read test
            it { should be_readable.by('owner') }
            it { should be_readable.by('group') }
            it { should be_readable.by('others') }

            # Write test
            it { should be_writable.by('owner') }
            # it { should_not be_writable.by('group') }
            # it { should_not be_writable.by('others') }

            # Exectuable test
            it { should be_executable.by('owner') }
            it { should be_executable.by('group') }
            it { should be_executable.by('others') }
        end
    end

    #########################
    ## Scripts
    #########################
    [
        "/usr/local/bin/apk-install",
        "/usr/local/bin/apk-upgrade",
        "/usr/local/bin/apt-install",
        "/usr/local/bin/apt-upgrade",
        "/usr/local/bin/yum-install",
        "/usr/local/bin/yum-upgrade",
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
            # it { should_not be_writable.by('group') }
            # it { should_not be_writable.by('others') }

            # Exectuable test
            it { should be_executable.by('owner') }
            it { should be_executable.by('group') }
            it { should be_executable.by('others') }
        end
    end

    #########################
    ## Devices
    #########################
    [
        "/dev/zero",
        "/dev/null",
        "/dev/random",
        "/dev/urandom",
        "/dev/tty",
        "/dev/full",
    ].each do |file|
        describe file("#{file}") do
            # Type check
            it { should be_character_device }

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
            it { should be_writable.by('group') }
            it { should be_writable.by('others') }

            # Exectuable test
            it { should_not be_executable.by('owner') }
            it { should_not be_executable.by('group') }
            it { should_not be_executable.by('others') }
        end
    end
end
