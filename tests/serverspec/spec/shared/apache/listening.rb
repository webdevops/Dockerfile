shared_examples 'apache::listening::public' do
    describe port(80) do
        it "apache should be listening", :retry => 20, :retry_wait => 3 do
            should be_listening
        end
    end
end
