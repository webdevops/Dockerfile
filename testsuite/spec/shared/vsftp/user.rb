shared_examples 'vsftp::user' do
    describe user('dev') do
      it { should exist }
      it { should have_login_shell '/bin/bash' }
      its(:encrypted_password) { should match(/^\$6\$.{16}\$.{86}$/) }
    end
end
