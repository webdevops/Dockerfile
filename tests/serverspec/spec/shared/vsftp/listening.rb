shared_examples 'vsftp::listening::public' do
    describe port(21) do
        it "should listen to port 21", :retry => 20, :retry_wait => 3 do
            should be_listening
        end
    end
end
