shared_examples 'base::packages' do
    describe package('logrotate')      do it { should be_installed } end
    describe package('openssh-server') do it { should be_installed } end
    describe package('dnsmasq')        do it { should be_installed } end
    describe package('postfix')        do it { should be_installed } end
    describe package('unzip')          do it { should be_installed } end
    describe package('bzip2')          do it { should be_installed } end
    describe package('wget')           do it { should be_installed } end
    describe package('curl')           do it { should be_installed } end
    describe package('moreutils')      do it { should be_installed } end
    describe package('rsync')          do it { should be_installed } end
    describe package('git')            do it { should be_installed } end
    describe package('nano')           do it { should be_installed } end
    describe package('net-tools')      do it { should be_installed } end
end
