shared_examples 'apache::layout' do
    #########################
    ## Directories
    #########################
    [
        "/opt/docker/etc/httpd",
        "/opt/docker/etc/httpd/conf.d",
        "/opt/docker/etc/httpd/vhost.common.d",
        "/opt/docker/bin/service.d/httpd.d",
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
    ## Files
    #########################
    [
        "/opt/docker/etc/httpd/global.conf",
        "/opt/docker/etc/httpd/main.conf",
        "/opt/docker/etc/httpd/php.conf",
        "/opt/docker/etc/httpd/vhost.conf",
        "/opt/docker/etc/httpd/vhost.ssl.conf",
        "/opt/docker/etc/httpd/vhost.common.d/01-boilerplate.conf",
    ].each do |file|
        describe file("#{file}") do
            # Type check
            it { should be_file }

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
            it { should_not be_executable.by('owner') }
            it { should_not be_executable.by('group') }
            it { should_not be_executable.by('others') }
        end
    end

    #########################
    ## Files
    #########################
    [
        "/opt/docker/bin/service.d/httpd.sh",
        "/opt/docker/bin/service.d/httpd.d/10-init.sh",
    ].each do |file|
        describe file("#{file}") do
            # Type check
            it { should be_file }
            it { should be_executable }

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
    ## SSL (special rights)
    #########################
    describe file('/opt/docker/etc/httpd/ssl') do
        # Type check
        it { should be_directory }

        # owner test
        it { should be_owned_by 'root' }
        it { should be_grouped_into 'root' }

        # read test
        it { should be_readable.by('owner') }
        it { should be_readable.by('group') }
        it { should_not be_readable.by('others') }
        it { should_not be_readable.by('application') }

        # write test
        it { should be_writable.by('owner') }
        # it { should_not be_writable.by('group') }
        # it { should_not be_writable.by('others') }
        # it { should_not be_writable.by_user('application') }

        # exectuable test
        it { should be_executable.by('owner') }
        it { should be_executable.by('group') }
        it { should_not be_executable.by('others') }
        it { should_not be_executable.by_user('application') }
    end

    describe file('/opt/docker/etc/httpd/ssl/server.crt') do
        # owner test
        it { should be_owned_by 'root' }
        it { should be_grouped_into 'root' }

        # read test
        it { should be_readable.by('owner') }
        it { should be_readable.by('group') }
        it { should_not be_readable.by('others') }
        it { should_not be_readable.by('application') }

        # write test
        it { should be_writable.by('owner') }
        # it { should_not be_writable.by('group') }
        # it { should_not be_writable.by('others') }
        # it { should_not be_writable.by_user('application') }

        # exectuable test
        it { should_not be_executable.by('owner') }
        it { should_not be_executable.by('group') }
        it { should_not be_executable.by('others') }
    end

    describe file('/opt/docker/etc/httpd/ssl/server.csr') do
        # owner test
        it { should be_owned_by 'root' }
        it { should be_grouped_into 'root' }

        # read test
        it { should be_readable.by('owner') }
        it { should be_readable.by('group') }
        it { should_not be_readable.by('others') }
        it { should_not be_readable.by('application') }

        # write test
        it { should be_writable.by('owner') }
        # it { should_not be_writable.by('group') }
        # it { should_not be_writable.by('others') }
        # it { should_not be_writable.by_user('application') }

        # exectuable test
        it { should_not be_executable.by('owner') }
        it { should_not be_executable.by('group') }
        it { should_not be_executable.by('others') }
    end

    describe file('/opt/docker/etc/httpd/ssl/server.key') do
        # owner test
        it { should be_owned_by 'root' }
        it { should be_grouped_into 'root' }

        # read test
        it { should be_readable.by('owner') }
        it { should be_readable.by('group') }
        it { should_not be_readable.by('others') }
        it { should_not be_readable.by('application') }

        # write test
        it { should be_writable.by('owner') }
        # it { should_not be_writable.by('group') }
        # it { should_not be_writable.by('others') }
        # it { should_not be_writable.by_user('application') }

        # exectuable test
        it { should_not be_executable.by('owner') }
        it { should_not be_executable.by('group') }
        it { should_not be_executable.by('others') }
    end
end
