shared_examples 'liquibase::liquibase' do
    describe command('liquibase --version') do
        its(:exit_status) { should eq 0 }
    end
end
