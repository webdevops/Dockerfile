shared_examples 'postfix::listening::public' do
    describe port(25) do
        it "postfix should be listening", :retry => 20, :retry_wait => 3 do
            should be_listening
        end
    end
end
