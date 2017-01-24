shared_examples 'postfix::listening::public' do
    describe port(25) do
        it "postfix should be listening", :retry => 5, :retry_wait => 10 do
            should be_listening
        end
    end
end
