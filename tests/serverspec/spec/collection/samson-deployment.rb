shared_examples 'collection::samson-deployment' do
    include_examples 'python::toolchain'
    include_examples 'ansible::toolchain::public'

    include_examples 'samson-deployment::layout'
    include_examples 'samson-deployment::deployment'

    # services
    include_examples 'samson-deployment::listening::public'
end

shared_examples 'collection::samson-deployment::php' do
    include_examples 'php::cli'
    include_examples 'php5::cli::version'
    include_examples 'php::composer'
end
