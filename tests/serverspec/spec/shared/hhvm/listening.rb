shared_examples 'hhvm::listening::public' do
    describe port(9000) do
        it "hhvm should be listening", :retry => 20, :retry_wait => 3 do
            should be_listening.with('tcp')
        end
    end
end

shared_examples 'hhvm::listening::local-only' do
    describe port(9000) do
        it "hhvm should NOT be listening public", :retry => 20, :retry_wait => 3 do
            should_not be_listening
        end
    end

    describe port(9000) do
        it "hhvm should be listening local", :retry => 20, :retry_wait => 3 do
            should be_listening.on('::1').with('tcp')
        end
    end
end
