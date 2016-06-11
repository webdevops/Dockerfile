shared_examples 'node::phantomjs::listening::public' do
    describe port(8065) do
        it "phantomjs should be listening" do
            wait_retry 30 do
                should be_listening
             end
        end
    end
end

shared_examples 'node::phantomjs::service::running' do
    describe command('service phantomjs pid | tr -d \'\n\'') do
        # must not pid 0
        its(:stdout) { should_not match %r!^0$! }
        # numeric match
        its(:stdout) { should     match %r!^[0-9]+$! }

        its(:exit_status) { should eq 0 }
    end
end
