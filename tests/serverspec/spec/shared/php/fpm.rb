shared_examples 'php-fpm::layout' do
    it "should have local php-fpm layout" do
        expect(file("/opt/docker/etc/php")).to be_directory
        expect(file("/opt/docker/etc/php/fpm")).to be_directory
        expect(file("/opt/docker/etc/php/fpm/pool.d")).to be_directory

        expect(file("/opt/docker/etc/php/php.ini")).to be_file
        expect(file("/opt/docker/etc/php/fpm/php-fpm.conf")).to be_file
        expect(file("/opt/docker/etc/php/fpm/pool.d/application.conf")).to be_file
    end
end

shared_examples 'php-fpm5::layout' do
    it "should have local php-fpm 5.x layout" do
        if $testConfiguration[:phpOfficialImage]
            expect(file("/usr/local/etc/php-fpm.d")).to be_symlink
        elsif os[:family] == 'redhat'
            expect(file("/etc/php-fpm.d")).to be_symlink
        elsif ['debian', 'ubuntu'].include?(os[:family])
            expect(file("/etc/php5/fpm/pool.d")).to be_symlink
        end
    end
end


shared_examples 'php-fpm7::layout' do
    it "should have local php-fpm 7.x layout" do
        if $testConfiguration[:phpOfficialImage]
            expect(file("/usr/local/etc/php-fpm.d")).to be_symlink
        elsif os[:family] == 'redhat'
            expect(file("/etc/php-fpm.d")).to be_symlink
        elsif ['debian', 'ubuntu'].include?(os[:family])
            if (os[:family] == 'ubuntu' and os[:version] == '17.10')
                expect(file("/etc/php/7.1/fpm/pool.d")).to be_symlink
            elsif (os[:family] == 'ubuntu' and os[:version] == '18.04')
                expect(file("/etc/php/7.2/fpm/pool.d")).to be_symlink
            elsif (os[:family] == 'debian' and os[:version] == '10')
                expect(file("/etc/php/7.3/fpm/pool.d")).to be_symlink
            else
                expect(file("/etc/php/7.0/fpm/pool.d")).to be_symlink
            end
        end
    end
end

shared_examples 'php-fpm::listening::public' do
    describe port(9000) do
        it "php-fpm should be listening", :retry => 10, :retry_wait => 3 do
           should be_listening.on('::').or(be_listening.on('0.0.0.0'))
        end
    end
end

shared_examples 'php-fpm::listening::local-only' do
    describe port(9000) do
        it "php-fpm should be listening local", :retry => 10, :retry_wait => 3 do
            should_not be_listening.on('0.0.0.0')
            should_not be_listening.on('::')
            should be_listening.on('::1').or(be_listening.on('127.0.0.1'))
        end
    end
end
