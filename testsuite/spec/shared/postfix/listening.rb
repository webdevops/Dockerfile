shared_examples 'postfix::listening::public' do
    describe port(25) do
        it "postfix should be listening" do
            wait_retry 30 do
                should be_listening
             end
        end
    end
end
