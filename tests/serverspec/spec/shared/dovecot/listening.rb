shared_examples 'dovecot::listening::public' do
    describe port(143) do
        it "dovecot should be listening", :retry => 5, :retry_wait => 10 do
            should be_listening
        end
    end
end
