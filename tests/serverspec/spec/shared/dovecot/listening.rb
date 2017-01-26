shared_examples 'dovecot::listening::public' do
    describe port(143) do
        it "dovecot should be listening", :retry => 20, :retry_wait => 3 do
            should be_listening
        end
    end
end
