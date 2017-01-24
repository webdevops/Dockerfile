shared_examples 'vsftp::listening::public' do
    describe port(21) do
        it "should listen to port 21", :retry => 5, :retry_wait => 10 do
            should be_listening
        end
    end
end
