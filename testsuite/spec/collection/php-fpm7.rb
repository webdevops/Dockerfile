shared_examples 'collection::php-fpm7' do
    include_examples 'php-fpm::layout'
    include_examples 'php-fpm7::layout'
end

shared_examples 'collection::php-fpm7::public' do
    # services
    include_examples 'php-fpm::listening::public'

    # test after services are up
    include_examples 'php-fpm::service::running'
end


shared_examples 'collection::php-fpm7::local-only' do
    # services
    include_examples 'php-fpm::listening::local-only'

    # test after services are up
    include_examples 'php-fpm::service::running'
end


shared_examples 'collection::php-fpm7::webserver-test' do
    include_examples 'php::fpm::test::sha1'
end

