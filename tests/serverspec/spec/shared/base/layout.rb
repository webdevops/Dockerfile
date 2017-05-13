shared_examples 'base::layout' do
    #########################
    ## Directories
    #########################
    [
        "/opt/",
        "/opt/docker",
        "/opt/docker/bin",
        "/opt/docker/bin/service.d",
        "/opt/docker/etc",
        "/opt/docker/etc/logrotate.d",
        "/opt/docker/etc/supervisor.d",
        "/opt/docker/etc/syslog-ng",
        "/opt/docker/provision/roles",
        "/opt/docker/provision/roles",
        "/opt/docker/provision/entrypoint.d",
        "/opt/docker/provision/onbuild.d",
        "/opt/docker/provision/bootstrap.d",
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
            it { should_not be_writable.by('group') }
            it { should_not be_writable.by('others') }

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
        "/opt/docker/etc/supervisor.conf",
        "/opt/docker/etc/logrotate.d/syslog-ng",
        "/opt/docker/etc/supervisor.conf",
        "/opt/docker/etc/supervisor.d/cron.conf",
        "/opt/docker/etc/supervisor.d/syslog.conf",
        "/opt/docker/etc/supervisor.d/dnsmasq.conf",
        "/opt/docker/etc/supervisor.d/postfix.conf",
        "/opt/docker/etc/supervisor.d/ssh.conf",
        "/opt/docker/etc/syslog-ng/syslog-ng.conf",
        "/opt/docker/etc/logrotate.d/syslog-ng",
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
        "/opt/docker/bin/bootstrap.sh",
        "/opt/docker/bin/config.sh",
        "/opt/docker/bin/control.sh",
        "/opt/docker/bin/entrypoint.sh",
        "/opt/docker/bin/service.d/syslog-ng.sh",
        "/opt/docker/bin/service.d/supervisor.sh",
        "/opt/docker/bin/entrypoint.d/cli.sh",
        "/opt/docker/bin/entrypoint.d/default.sh",
        "/opt/docker/bin/entrypoint.d/noop.sh",
        "/opt/docker/bin/entrypoint.d/root.sh",
        "/opt/docker/bin/entrypoint.d/supervisord.sh",
        "/opt/docker/bin/service.d/dnsmasq.sh",
        "/opt/docker/bin/service.d/postfix.sh",
        "/usr/local/bin/service",
        "/usr/local/bin/docker-service-enable",
        "/usr/local/bin/docker-service-disable",
        "/usr/local/bin/docker-provision",
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
            it { should_not be_writable.by('group') }
            it { should_not be_writable.by('others') }

            # Exectuable test
            it { should be_executable.by('owner') }
            it { should be_executable.by('group') }
            it { should be_executable.by('others') }
        end
    end

    #########################
    ## cronjobs which should not exists
    #########################
    [
        "/etc/cron.daily/logrotate",
        "/etc/cron.daily/apt-compat",
        "/etc/cron.daily/dpkg",
        "/etc/cron.daily/passwd",
        "/etc/cron.daily/0yum-daily.cron",
        "/etc/cron.daily/logrotate",
        "/etc/cron.hourly/0yum-hourly.cron",
        "/etc/periodic/daily/logrotate",
    ].each do |file|
        describe file("#{file}") do
            # Type check
            it { should_not be_file }
        end
    end
end
