shared_examples 'web::test::pi' do
    describe command('wget -O- http://localhost/pi-number.html') do
        its(:stdout) { should     contain('3.14159265359') }
    end
end
