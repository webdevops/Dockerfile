shared_examples 'hhvm::service::running' do
    describe command("service hhvm check") do
        it "should have running hhvm daemon", :retry => 5, :retry_wait => 10 do
            its(:stdout) { should match 'ok' }
            its(:exit_status) { should eq 0 }
        end
    end

    describe command('service hhvm pid | tr -d \'\n\'') do
        # must not pid 0
        its(:stdout) { should_not match %r!^0$! }
        # numeric match
        its(:stdout) { should     match %r!^[0-9]+$! }

        its(:exit_status) { should eq 0 }
    end
end
