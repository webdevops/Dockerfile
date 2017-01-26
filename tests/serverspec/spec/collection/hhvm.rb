shared_examples 'collection::hhvm' do
    include_examples 'hhvm::layout'
    include_examples 'hhvm::cli::version'
    include_examples 'php::cli::test::sha1'
    include_examples 'php::composer'

    # services
    include_examples 'hhvm::listening::public'

    # test after services are up
    include_examples 'hhvm::service::running'
end

shared_examples 'collection::hhvm::webserver-test' do
    include_examples 'php::fpm::test::sha1'
end
