shared_examples 'collection::varnish' do
    include_examples 'varnish::layout'

    # services
    include_examples 'varnish::listening::public'
end

