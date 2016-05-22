shared_examples 'collection::php7' do
    include_examples 'php::cli'
    include_examples 'php7::cli::version'
    include_examples 'php::modules'
    include_examples 'php7::modules'
    include_examples 'php::cli::configuration'
    include_examples 'php::cli::test::sha1'
    include_examples 'php::cli::test::php_ini_scanned_files'
    include_examples 'php::cli::test::php_sapi_name'
    include_examples 'php::composer'

    include_examples 'misc::graphicsmagick'
    include_examples 'misc::imagemagick'
end

shared_examples 'collection::php7::production' do
    include_examples 'collection::php7'
    include_examples 'php::modules::production'
    include_examples 'php::cli::configuration::production'
end

shared_examples 'collection::php7::development' do
    include_examples 'collection::php7'
    include_examples 'php::modules::development'
    include_examples 'php::cli::configuration::development'
end
