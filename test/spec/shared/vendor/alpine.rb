shared_examples 'vendor::alpine::apk' do
    describe command('apk add --update-cache --no-network bash') do
        its(:stdout) { should_not contain('ERROR') }
        its(:stdout) { should_not contain('unsatisfiable constraints') }

        its(:exit_status) { should eq 0 }
    end
end
