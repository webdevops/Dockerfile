shared_examples 'collection::php5' do
    include_examples 'php::cli'
    include_examples 'php5::cli::version'
    include_examples 'php::modules'
    include_examples 'php5::modules'
    include_examples 'php::cli::test::sha1'
    include_examples 'php::composer'
end
