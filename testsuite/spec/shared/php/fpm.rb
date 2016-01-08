shared_examples 'php::fpm::public' do
    describe port(9000) do
        it "php-fpm should be running" do
            wait_retry 10 do
                should be_listening.on('0.0.0.0').with('tcp')
             end
        end
    end
end

shared_examples 'php::fpm::private' do
    describe port(9000) do
        it "php-fpm should be running" do
            wait_retry 10 do
                should_not be_listening.on('0.0.0.0')
             end
        end
    end
end
