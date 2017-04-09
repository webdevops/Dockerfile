shared_examples 'liquidsoap::listening::public' do
    describe port(1234) do
        it "hhvm should be listening", :retry => 20, :retry_wait => 3 do
            should be_listening
        end
    end
end
