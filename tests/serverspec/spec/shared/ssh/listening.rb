shared_examples 'ssh::listening::public' do
    describe port(22) do
        it "ssh should be listening" do
            wait_retry 30 do
                should be_listening
             end
        end
    end
end
