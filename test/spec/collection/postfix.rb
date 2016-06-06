shared_examples 'collection::postfix' do
    include_examples 'postfix::layout'

    # services
    include_examples 'postfix::listening::public'

    # test after services are up
    include_examples 'postfix::service::running'
end
