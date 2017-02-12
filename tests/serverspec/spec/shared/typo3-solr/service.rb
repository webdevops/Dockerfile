shared_examples 'typo3-solr::service::running' do
    describe "service solr check" do
        it "should have running solr daemon", :retry => 20, :retry_wait => 3 do
            check_if_service_is_running_stable("java")
        end
    end
end
