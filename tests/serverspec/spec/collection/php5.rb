shared_examples 'collection::php5' do
    include_examples 'php::layout'
    include_examples 'php::cli'
    include_examples 'php5::cli::version'
    include_examples 'php::modules'
    include_examples 'php5::modules'
    include_examples 'php::cli::configuration'
    include_examples 'php::cli::test::sha1'
    include_examples 'php::cli::test::php_ini_scanned_files'
    include_examples 'php::cli::test::php_sapi_name'
    include_examples 'php::composer'
    include_examples 'php::pear'

    include_examples 'misc::graphicsmagick'
    include_examples 'misc::imagemagick'
    include_examples 'misc::ghostscript'
end

shared_examples 'collection::php5::production' do
    include_examples 'collection::php5'
    include_examples 'php::modules::production'
    include_examples 'php::cli::configuration::production'
end

shared_examples 'collection::php5::development' do
    include_examples 'collection::php5'
    include_examples 'php::modules::development'
    include_examples 'php::cli::configuration::development'
end
