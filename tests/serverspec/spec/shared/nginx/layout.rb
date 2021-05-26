shared_examples 'nginx::layout' do
    #########################
    ## Directories
    #########################
    [
        "/opt/docker/etc/nginx",
        "/opt/docker/etc/nginx/conf.d",
        "/opt/docker/etc/nginx/vhost.common.d",
        "/opt/docker/bin/service.d/nginx.d",
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
        "/opt/docker/etc/nginx/global.conf",
        "/opt/docker/etc/nginx/main.conf",
        "/opt/docker/etc/nginx/php.conf",
        "/opt/docker/etc/nginx/vhost.conf",
        "/opt/docker/etc/nginx/vhost.ssl.conf",
        "/opt/docker/etc/nginx/vhost.common.d/10-location-root.conf",
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
        "/opt/docker/bin/service.d/nginx.sh",
        "/opt/docker/bin/service.d/nginx.d/10-init.sh",
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
    describe file('/opt/docker/etc/nginx/ssl') do
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

    describe file('/opt/docker/etc/nginx/ssl/server.crt') do
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

    describe file('/opt/docker/etc/nginx/ssl/server.csr') do
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

    describe file('/opt/docker/etc/nginx/ssl/server.key') do
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
