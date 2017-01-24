shared_examples 'ssh::listening::public' do
    describe port(22) do
        it "ssh should be listening", :retry => 20, :retry_wait => 3 do
            should be_listening
        end
    end
end
