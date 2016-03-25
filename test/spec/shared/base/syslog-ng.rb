shared_examples 'base::syslog-ng' do
    describe package('syslog-ng') do
      it { should be_installed }
    end
end
