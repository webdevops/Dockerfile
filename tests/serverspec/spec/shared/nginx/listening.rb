shared_examples 'nginx::listening::public' do
    describe port(80) do
        it "nginx should be listening", :retry => 5, :retry_wait => 10 do
            should be_listening
        end
    end
end
