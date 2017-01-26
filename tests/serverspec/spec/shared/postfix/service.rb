shared_examples 'postfix::service::running' do
    describe "service postfix check" do
        it "should have running postfix daemon", :retry => 20, :retry_wait => 3 do
            cmd = command("service postfix check")
            expect(cmd.stdout).to match('ok')
            expect(cmd.exit_status).to eq 0
        end
    end

    describe command('service postfix pid | tr -d \'\n\'') do
        # must not pid 0
        its(:stdout) { should_not match %r!^0$! }
        # numeric match
        its(:stdout) { should     match %r!^[0-9]+$! }

        its(:exit_status) { should eq 0 }
    end

    describe command('mailq') do
        its(:stdout) { should_not contain('warning') }
        its(:stderr) { should_not contain('warning') }

        its(:exit_status) { should eq 0 }
    end
end
