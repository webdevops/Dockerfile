shared_examples 'vsftp::user' do
    describe user('application') do
      it { should exist }
      it { should have_login_shell '/bin/bash' }
    end
end
