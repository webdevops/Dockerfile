shared_examples 'php-fpm::layout' do
    it "should have local php-fpm layout" do
        expect(file("/opt/docker/etc/php")).to be_directory
        expect(file("/opt/docker/etc/php/fpm")).to be_directory
        expect(file("/opt/docker/etc/php/fpm/pool.d")).to be_directory

        expect(file("/opt/docker/etc/php/fpm/php-fpm.conf")).to be_file
        expect(file("/opt/docker/etc/php/fpm/pool.d/application.conf")).to be_file
    end
end

shared_examples 'php-fpm::listening::public' do
    describe port(9000) do
        it "php-fpm should be listening" do
            wait_retry 30 do
                should be_listening.on('0.0.0.0').with('tcp')
             end
        end
    end
end

shared_examples 'php-fpm::listening::local-only' do
    describe port(9000) do
        it "php-fpm should NOT be listening public" do
            wait_retry 15 do
                should_not be_listening.on('0.0.0.0')
             end
        end
    end

    describe port(9000) do
        it "php-fpm should be listening local" do
            wait_retry 30 do
                should be_listening.on('127.0.0.1').with('tcp')
             end
        end
    end
end
