shared_examples 'collection::development' do

    # services
    include_examples 'ssh::listening::public'

    # test after services are up
    include_examples 'ssh::service::running'

end
