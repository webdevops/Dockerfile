shared_examples 'php::fpm::public' do
    describe port(9000) do
        it "php-fpm should be listening" do
            wait_retry 15 do
                should be_listening.on('0.0.0.0').with('tcp')
             end
        end
    end
end

shared_examples 'php::fpm::private' do
    describe port(9000) do
        it "php-fpm should NOT be listening" do
            wait_retry 15 do
                should_not be_listening.on('0.0.0.0')
             end
        end
    end
end
