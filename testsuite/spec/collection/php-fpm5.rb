shared_examples 'collection::php-fpm5' do
    include_examples 'php-fpm::layout'
    include_examples 'php-fpm5::layout'
end

shared_examples 'collection::php-fpm5::public' do
    # services
    include_examples 'php-fpm::listening::public'

    # test after services are up
    include_examples 'php-fpm::service::running'
end


shared_examples 'collection::php-fpm5::local-only' do
    # services
    include_examples 'php-fpm::listening::local-only'

    # test after services are up
    include_examples 'php-fpm::service::running'
end


shared_examples 'collection::php-fpm::webserver-test' do
    include_examples 'php::fpm::test::sha1'
    include_examples 'php::fpm::test::php_ini_scanned_files'
end

