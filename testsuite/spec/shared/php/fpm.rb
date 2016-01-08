shared_examples 'php::fpm::public' do
    describe port(9000) do
        it "php-fpm should be running" do
            wait_retry 30 do
                should be_listening
             end
        end
    end
end
