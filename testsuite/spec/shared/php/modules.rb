shared_examples 'php::module::xdebug::absent' do
    describe command('php -m') do
        its(:stdout) { should_not contain('xdebug') }
    end
end
