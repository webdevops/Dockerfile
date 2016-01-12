shared_examples 'collection::apache' do
    # services
    include_examples 'apache::listening::public'

    # test after services are up
    include_examples 'apache::modules'
    include_examples 'apache::service::running'
end

