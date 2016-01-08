shared_examples 'vsftp::listening::public' do
    describe port(20) do
        it "vsftp should be listening" do
            wait_retry 30 do
                should be_listening
             end
        end
    end

    describe port(21) do
        it "vsftp should be listening" do
            wait_retry 30 do
                should be_listening
             end
        end
    end
end
