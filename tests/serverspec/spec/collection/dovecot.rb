shared_examples 'collection::dovecot' do
    include_examples 'dovecot::layout'

    # services
    include_examples 'dovecot::listening::public'

    # test after services are up
    include_examples 'dovecot::service::running'
end
