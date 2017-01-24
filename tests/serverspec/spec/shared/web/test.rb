shared_examples 'web::test::pi' do
    [
        'http://localhost/pi-number.html',
        'https://localhost/pi-number.html'
    ].each do |url|
        describe command("curl --insecure --silent --retry 10 --fail #{url}") do
            it "should have running and answering webserver", :retry => 5, :retry_wait => 10 do
                its(:stdout) { should     contain('3.14159265359') }
                its(:exit_status) { should eq 0 }
            end
        end
    end
end
