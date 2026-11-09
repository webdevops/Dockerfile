shared_examples 'nginx::layout' do
    #########################
    ## Directories
    #########################
    [
        "/opt/docker/etc/nginx",
        "/opt/docker/etc/nginx/conf.d",
        "/opt/docker/etc/nginx/vhost.common.d",
        "/opt/docker/etc/nginx/vhost.ipv6",
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
    ## IPv6 listen directives of the default vhost
    #########################
    # The start script deletes them if the kernel has no IPv6. Serverspec runs on
    # the Docker host, which shares its kernel with the container.
    ipv6_available = File.exist?('/proc/net/if_inet6')

    describe file('/opt/docker/etc/nginx/vhost.conf') do
        its(:content) { should include 'include /opt/docker/etc/nginx/vhost.ipv6/listen-80*.conf;' }
        its(:content) { should include 'include /opt/docker/etc/nginx/vhost.ipv6/listen-443*.conf;' }
    end

    {
        "/opt/docker/etc/nginx/vhost.ipv6/listen-80.conf"  => "listen [::]:80 default_server;",
        "/opt/docker/etc/nginx/vhost.ipv6/listen-443.conf" => "listen [::]:443 ssl default_server;",
    }.each do |file, directive|
        describe file("#{file}"), :if => ipv6_available do
            it { should be_file }
            its(:content) { should include directive }

            # Owner test
            it { should be_owned_by 'root' }
            it { should be_grouped_into 'root' }

            # Read test
            it { should be_readable.by('owner') }
            it { should be_readable.by('group') }
            it { should be_readable.by('others') }

            # Exectuable test
            it { should_not be_executable.by('owner') }
            it { should_not be_executable.by('group') }
            it { should_not be_executable.by('others') }
        end

        describe file("#{file}"), :unless => ipv6_available do
            it { should_not exist }
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
