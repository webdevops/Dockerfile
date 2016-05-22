shared_examples 'varnish::listening::public' do
    describe port(80) do
        it "varnish should be listening" do
            wait_retry 30 do
                should be_listening
             end
        end
    end
end
