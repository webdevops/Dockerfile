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


shared_examples 'collection::php-fpm7::webserver-test::development' do
    include_examples 'php-fpm::modules'
    include_examples 'php-fpm7::modules'
    include_examples 'php-fpm::modules::development'
    include_examples 'php::fpm::test::sha1'
    include_examples 'php::fpm::test::php_ini_scanned_files'
    include_examples 'php::fpm::test::php_sapi_name'
    include_examples 'php::fpm::test::process_user_id'
    include_examples 'php7::fpm::test::version'
end


shared_examples 'collection::php-fpm7::webserver-test::production' do
    include_examples 'php-fpm::modules'
    include_examples 'php-fpm7::modules'
    include_examples 'php-fpm::modules::production'
    include_examples 'php::fpm::test::sha1'
    include_examples 'php::fpm::test::php_ini_scanned_files'
    include_examples 'php::fpm::test::php_sapi_name'
    include_examples 'php::fpm::test::process_user_id'
    include_examples 'php7::fpm::test::version'
end

