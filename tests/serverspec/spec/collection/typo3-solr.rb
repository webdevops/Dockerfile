shared_examples 'collection::typo3-solr' do
    include_examples 'typo3-solr::layout'

    # currently there are images without "ps" support
    #include_examples 'typo3-solr::service::running'

    include_examples 'typo3-solr::listening::public'
    include_examples 'typo3-solr::test'
end
