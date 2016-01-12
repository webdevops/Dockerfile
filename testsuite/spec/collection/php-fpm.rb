shared_examples 'collection::php-fpm::public' do
    # services
    include_examples 'php-fpm::listening::public'

    # test after services are up
    include_examples 'php-fpm::service::running'
end


shared_examples 'collection::php-fpm::local-only' do
    # services
    include_examples 'php-fpm::listening::local-only'

    # test after services are up
    include_examples 'php-fpm::service::running'
end


shared_examples 'collection::php-fpm::webserver-test' do
    include_examples 'php::fpm::test::sha1'
end

