shared_examples 'collection::typo3-solr' do
    include_examples 'typo3-solr::layout'
    include_examples 'typo3-solr::service::running'
    include_examples 'typo3-solr::listening::public'
end
