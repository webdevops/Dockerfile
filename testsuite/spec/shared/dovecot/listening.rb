shared_examples 'dovecot::listening::public' do
    describe port(143) do
        it "apache should be listening" do
            wait_retry 30 do
                should be_listening
             end
        end
    end

    describe port(993) do
        it "apache should be listening" do
            wait_retry 30 do
                should be_listening
             end
        end
    end

end
