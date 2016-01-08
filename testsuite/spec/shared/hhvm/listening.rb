shared_examples 'hhvm::listening::public' do
    describe port(9000) do
        it "hhvm should be listening" do
            wait_retry 15 do
                should be_listening.with('tcp6')
             end
        end
    end
end

shared_examples 'hhvm::listening::local-only' do
    describe port(9000) do
        it "hhvm should NOT be listening public" do
            wait_retry 15 do
                should_not be_listening
             end
        end
    end

    describe port(9000) do
        it "hhvm should be listening local" do
            wait_retry 15 do
                should be_listening.on('::1').with('tcp6')
             end
        end
    end
end
