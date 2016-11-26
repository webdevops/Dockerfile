shared_examples 'base-app::packages' do
    [
        'openssh-server',
        'dnsmasq',
        'postfix',
        'unzip',
        'bzip2',
        'wget',
        'curl',
        'moreutils',
        'rsync',
        'git',
        'nano',
        'net-tools',
    ].each do |package|
        describe package("#{package}") do
            it { should be_installed }
        end
    end
end
