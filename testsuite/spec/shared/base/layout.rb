shared_examples 'base::layout' do
    it "includes the /opt/docker/bin files" do
        expect(file("/opt/docker")).to be_directory
        expect(file("/opt/docker/bin")).to be_directory
        expect(file("/opt/docker/bin/bootstrap.d")).to be_directory
        expect(file("/opt/docker/bin/entrypoint.d")).to be_directory
        expect(file("/opt/docker/bin/onbuild.d")).to be_directory
        expect(file("/opt/docker/bin/service.d")).to be_directory

        expect(file("/opt/docker/bin/bootstrap.sh")).to be_file
        expect(file("/opt/docker/bin/config.sh")).to be_file
        expect(file("/opt/docker/bin/control.sh")).to be_file
        expect(file("/opt/docker/bin/entrypoint.sh")).to be_file
        expect(file("/opt/docker/bin/logwatch.sh")).to be_file
        expect(file("/opt/docker/bin/provision.sh")).to be_file
    end

    it "includes the /opt/docker/etc files" do
        expect(file("/opt/docker/etc")).to be_directory
        expect(file("/opt/docker/etc/logrotate.d")).to be_directory
        expect(file("/opt/docker/etc/supervisor.d")).to be_directory
        expect(file("/opt/docker/etc/syslog-ng")).to be_directory

        expect(file("/opt/docker/etc/supervisor.conf")).to be_file
        expect(file("/opt/docker/etc/logrotate.d/syslog-ng")).to be_file
        expect(file("/opt/docker/etc/supervisor.d/cron.conf")).to be_file
        expect(file("/opt/docker/etc/supervisor.d/dnsmasq.conf")).to be_file
        expect(file("/opt/docker/etc/supervisor.d/postfix.conf")).to be_file
        expect(file("/opt/docker/etc/supervisor.d/ssh.conf")).to be_file
        expect(file("/opt/docker/etc/supervisor.d/syslog-ng.conf")).to be_file
        expect(file("/opt/docker/etc/syslog-ng/syslog-ng.conf")).to be_file
    end

    it "includes the /opt/docker/etc files" do
        expect(file("/opt/docker/provision/roles")).to be_directory
        expect(file("/opt/docker/provision/roles")).to be_directory
        expect(file("/opt/docker/provision/roles/webdevops-base")).to be_directory
        expect(file("/opt/docker/provision/roles/webdevops-cleanup")).to be_directory

        expect(file("/opt/docker/etc/supervisor.conf")).to be_file
        expect(file("/opt/docker/etc/logrotate.d/syslog-ng")).to be_file
    end
end
