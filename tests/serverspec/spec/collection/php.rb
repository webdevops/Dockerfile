shared_examples 'collection::php' do
    include_examples 'php::layout'
    include_examples 'php::cli'
    include_examples 'php::cli::version'
    include_examples 'php::modules::ftp_ssl'
    include_examples 'php::modules'
    include_examples 'php::modules::versioned'
    include_examples 'php::cli::configuration'
    include_examples 'php::cli::test::sha1'
    include_examples 'php::cli::test::avif'
    include_examples 'php::cli::test::imap' if $testConfiguration[:phpImap]
    include_examples 'php::cli::test::php_ini_scanned_files'
    include_examples 'php::cli::test::php_sapi_name'
    include_examples 'php::composer'

    include_examples 'misc::graphicsmagick'
    include_examples 'misc::imagemagick'
    include_examples 'misc::ghostscript'
end

shared_examples 'collection::php::production' do
    include_examples 'collection::php'
    include_examples 'php::modules::production'
    include_examples 'php::cli::configuration::production'
end

shared_examples 'collection::php::development' do
    include_examples 'collection::php'
    include_examples 'php::modules::development'
    include_examples 'php::cli::configuration::development'
end
