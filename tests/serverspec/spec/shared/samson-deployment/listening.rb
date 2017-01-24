shared_examples 'samson-deployment::listening::public' do
    describe port(80) do
        it "samson (nginx reverse proxy) should be listening", :retry => 20, :retry_wait => 3 do
            should be_listening
        end
    end

# will not start because github api keys are needed
#    describe port(9000) do
#        it "samson should be listening", :retry => 20, :retry_wait => 3 do
#            should be_listening
#        end
#    end
end
