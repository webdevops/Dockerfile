shared_examples 'collection::node' do
    include_examples 'misc::node'
end

shared_examples 'collection::node::phantomjs' do
	include_examples 'misc::phantomjs'
    include_examples 'node::phantomjs::listening::public'
    include_examples 'node::phantomjs::service::running'
end
