shared_examples 'web::test::pi' do
    describe command('wget -O- http://localhost/pi-number.html') do
        its(:stdout) { should     contain('3.14159265359') }

        its(:exit_status) { should eq 0 }
    end

    describe command('curl --insecure https://localhost/pi-number.html') do
        its(:stdout) { should     contain('3.14159265359') }

        its(:exit_status) { should eq 0 }
    end
end

