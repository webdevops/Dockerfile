shared_examples 'base::ansible::debian' do
    describe file('/usr/local/bin/ansible') do
      it { should be_executable }
    end

    describe file('/usr/local/bin/ansible-playbook') do
      it { should be_executable }
    end

end

shared_examples 'base::ansible::redhat' do
    describe file('/usr/bin/ansible') do
      it { should be_executable }
    end

    describe file('/usr/bin/ansible-playbook') do
      it { should be_executable }
    end

end
