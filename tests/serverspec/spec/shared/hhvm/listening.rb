shared_examples 'hhvm::listening::public' do
    describe port(9000) do
        it "hhvm should be listening", :retry => 20, :retry_wait => 3 do
            should be_listening.on('::').or(be_listening.on('0.0.0.0'))
        end
    end
end

shared_examples 'hhvm::listening::local-only' do
    describe port(9000) do
        it "hhvm should be listening local", :retry => 20, :retry_wait => 3 do
            should_not be_listening.on('::')
            should_not be_listening.on('0.0.0.0')
            should be_listening.on('::1').or(be_listening.on('127.0.0.1'))
        end
    end
end
