shared_examples 'php::pear' do
    describe command('pear') do
        its(:exit_status) { should eq 0 }
    end
end
