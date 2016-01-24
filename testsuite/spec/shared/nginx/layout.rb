shared_examples 'nginx::layout' do
    describe file('/opt/docker/etc/nginx/ssl') do
        # owner test
        it { should be_owned_by 'root' }
        it { should be_grouped_into 'root' }

        # read test
        it { should be_readable.by('owner') }
        it { should be_readable.by('group') }
        it { should_not be_readable.by('others') }
        it { should_not be_readable.by('application') }

        # write test
        it { should be_writable.by('owner') }
        it { should_not be_writable.by('group') }
        it { should_not be_writable.by('others') }
        it { should_not be_writable.by_user('application') }

        # exectuable test
        it { should be_executable.by('owner') }
        it { should be_executable.by('group') }
        it { should_not be_executable.by('others') }
        it { should_not be_executable.by_user('application') }
    end

    describe file('/opt/docker/etc/nginx/ssl/server.crt') do
        # owner test
        it { should be_owned_by 'root' }
        it { should be_grouped_into 'root' }

        # read test
        it { should be_readable.by('owner') }
        it { should be_readable.by('group') }
        it { should_not be_readable.by('others') }
        it { should_not be_readable.by('application') }

        # write test
        it { should be_writable.by('owner') }
        it { should_not be_writable.by('group') }
        it { should_not be_writable.by('others') }
        it { should_not be_writable.by_user('application') }

        # exectuable test
        it { should_not be_executable.by('owner') }
        it { should_not be_executable.by('group') }
        it { should_not be_executable.by('others') }
    end

    describe file('/opt/docker/etc/nginx/ssl/server.csr') do
        # owner test
        it { should be_owned_by 'root' }
        it { should be_grouped_into 'root' }

        # read test
        it { should be_readable.by('owner') }
        it { should be_readable.by('group') }
        it { should_not be_readable.by('others') }
        it { should_not be_readable.by('application') }

        # write test
        it { should be_writable.by('owner') }
        it { should_not be_writable.by('group') }
        it { should_not be_writable.by('others') }
        it { should_not be_writable.by_user('application') }

        # exectuable test
        it { should_not be_executable.by('owner') }
        it { should_not be_executable.by('group') }
        it { should_not be_executable.by('others') }
    end

    describe file('/opt/docker/etc/nginx/ssl/server.key') do
        # owner test
        it { should be_owned_by 'root' }
        it { should be_grouped_into 'root' }

        # read test
        it { should be_readable.by('owner') }
        it { should be_readable.by('group') }
        it { should_not be_readable.by('others') }
        it { should_not be_readable.by('application') }

        # write test
        it { should be_writable.by('owner') }
        it { should_not be_writable.by('group') }
        it { should_not be_writable.by('others') }
        it { should_not be_writable.by_user('application') }

        # exectuable test
        it { should_not be_executable.by('owner') }
        it { should_not be_executable.by('group') }
        it { should_not be_executable.by('others') }
    end
end
