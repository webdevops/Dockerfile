shared_examples 'typo3-solr::listening::public' do
    describe port(8983) do
        it "solr should be listening", :retry => 20, :retry_wait => 3 do
            should be_listening
        end
    end
end
