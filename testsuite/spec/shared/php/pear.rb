shared_examples 'php::pear' do
    describe command('pear') do
        if (! (os[:family] == 'ubuntu' and os[:version] == '16.04') )
            its(:exit_status) { should eq 0 }
        end
    end
end
