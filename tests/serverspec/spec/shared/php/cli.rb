shared_examples 'php::cli' do
    describe command('php -i') do
        its(:exit_status) { should eq 0 }
    end
end
