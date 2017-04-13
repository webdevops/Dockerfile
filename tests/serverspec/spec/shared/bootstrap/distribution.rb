shared_examples 'bootstrap::distribution' do

    #########################
    # CentOS
    #########################

    describe command('docker-image-info family'), :if => os[:family] == 'redhat' do
        its(:stdout) { should contain("RedHat") }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info dist'), :if => os[:family] == 'redhat' do
        its(:stdout) { should contain("CentOS") }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info dist-version'), :if => os[:family] == 'redhat' do
        its(:stdout) { should contain(os[:version]) }

        its(:exit_status) { should eq 0 }
    end

    describe command('cat /etc/redhat-release'), :if => os[:family] == 'redhat' do
        its(:stdout) { should contain('CentOS') }
        its(:stdout) { should contain('Linux release ' + os[:version] + '.') }

        its(:exit_status) { should eq 0 }
    end

    #########################
    # Ubuntu
    #########################

    describe command('docker-image-info family'), :if => os[:family] == 'ubuntu' do
        its(:stdout) { should contain("Debian") }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info dist'), :if => os[:family] == 'ubuntu' do
        its(:stdout) { should contain("Ubuntu") }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info lsb'), :if => os[:family] == 'ubuntu' do
        its(:stdout) { should contain("Distributor ID:\tUbuntu") }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info dist-version'), :if => os[:family] == 'ubuntu' do
        its(:stdout) { should contain(os[:version]) }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info lsb'), :if => os[:family] == 'ubuntu' do
        its(:stdout) { should contain("Release:\t" + os[:version] + '.') }

        its(:exit_status) { should eq 0 }
    end

    #########################
    # Debian
    #########################

    describe command('docker-image-info family'), :if => os[:family] == 'debian' do
        its(:stdout) { should contain("Debian") }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info dist'), :if => os[:family] == 'debian' do
        its(:stdout) { should contain("Debian") }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info lsb'), :if => os[:family] == 'debian' do
        its(:stdout) { should contain("Distributor ID:\tDebian") }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info dist-version'), :if => os[:family] == 'debian' do
        its(:stdout) { should contain(os[:version]) }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info lsb'), :if => os[:family] == 'debian' do
        its(:stdout) { should contain("Release:\t" + os[:version] + '.') }

        its(:exit_status) { should eq 0 }
    end

    #########################
    # Alpine
    #########################

    describe command('docker-image-info family'), :if => os[:family] == 'alpine' do
        its(:stdout) { should contain("Alpine") }

        its(:exit_status) { should eq 0 }
    end


    describe command('docker-image-info dist'), :if => os[:family] == 'alpine' do
        its(:stdout) { should contain("Alpine") }

        its(:exit_status) { should eq 0 }
    end

    describe command('docker-image-info dist-version'), :if => os[:family] == 'alpine' do
        its(:stdout) { should contain(os[:version]) }

        its(:exit_status) { should eq 0 }
    end

    describe command('sed -e "s/^/Release: /" /etc/alpine-release'), :if => os[:family] == 'alpine' do
        its(:stdout) { should contain('Release: ' + os[:version] + '.') }

        its(:exit_status) { should eq 0 }
    end
end
