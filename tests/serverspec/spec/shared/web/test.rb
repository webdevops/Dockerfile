shared_examples 'web::test::pi' do
    [
        'http://localhost/pi-number.html',
        'https://localhost/pi-number.html'
    ].each do |url|
        describe url do
            it "should have running and answering webserver", :retry => 20, :retry_wait => 3 do
                cmd = command("curl --insecure --silent --retry 10 --fail #{url}")
                expect(cmd.stdout).to contain('3.14159265359')
                expect(cmd.exit_status).to eq 0
            end
        end
    end
end
