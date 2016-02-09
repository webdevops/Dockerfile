shared_examples 'collection::nginx' do
    include_examples 'nginx::layout'

    # services
    include_examples 'nginx::listening::public'

    # test after services are up
    include_examples 'nginx::service::running'

    wait_retry 30 do
        include_examples 'web::test::pi'
    end
end

