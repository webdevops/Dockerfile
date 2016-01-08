shared_examples 'apache::listening' do
    describe port(80) do
        it "php-fpm should be running" do
            wait_retry 10 do
                should be_listening.on('0.0.0.0').with('tcp')
             end
        end
    end
end
