shared_examples 'typo3-solr::test' do
    describe 'solr-response' do
        it 'solr version should be correct', :retry => 20, :retry_wait => 3 do
            content = get_url('http://localhost:8983/solr/admin/info/system?wt=json')
            content = JSON.parse(content)

            expect(content['solr_home']).to match('/opt/solr/server/solr')
            expect(content['lucene']['solr-spec-version']).to match(ENV['SOLR_VERSION'])
        end
    end
end
