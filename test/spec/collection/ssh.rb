shared_examples 'collection::ssh' do
    # services
    include_examples 'ssh::listening::public'

    # test after services are up
    include_examples 'ssh::service::running'
end
