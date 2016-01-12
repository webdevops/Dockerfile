shared_examples 'collection::nginx' do
    # services
    include_examples 'nginx::listening::public'

    # test after services are up
    include_examples 'nginx::service::running'
end

