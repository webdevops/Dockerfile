shared_examples 'collection::php7' do
    include_examples 'php::cli'
    include_examples 'php7::cli::version'
    include_examples 'php::modules'
    include_examples 'php7::modules'
    include_examples 'php::cli::test::sha1'
    include_examples 'php::composer'
end
