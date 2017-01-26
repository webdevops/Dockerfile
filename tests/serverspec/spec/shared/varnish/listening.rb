shared_examples 'varnish::listening::public' do
    describe port(80) do
        it "varnish should be listening", :retry => 20, :retry_wait => 3 do
            should be_listening
        end
    end
end
