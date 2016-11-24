shared_examples 'base-app::application-user' do
    describe user('application') do
      it { should exist }
    end

    describe user('application') do
      it { should belong_to_group 'application' }
    end

    describe user('application') do
      it { should have_uid 1000 }
    end

    describe user('application') do
      it { should have_home_directory '/home/application' }
    end

    describe user('application') do
      it { should have_login_shell '/bin/bash' }
    end

end
