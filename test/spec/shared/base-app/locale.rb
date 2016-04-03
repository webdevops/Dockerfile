shared_examples 'base-app::locales' do
    describe command('locale -a'), :if => ['debian', 'ubuntu', 'redhat'].include?(os[:family]) do
        its(:stdout) { should contain('C') }
        its(:stdout) { should contain('POSIX') }
        its(:stdout) { should contain('en_US.utf8') }
        its(:stdout) { should contain('de_DE.utf8') }
        its(:stdout) { should contain('de_LU') }
        its(:stdout) { should contain('es_BO') }
        its(:stdout) { should contain('st_ZA') }
        its(:stdout) { should contain('zu_ZA') }
        its(:stdout) { should contain('ca_ES') }
        its(:stdout) { should contain('fr_FR') }

        its(:exit_status) { should eq 0 }
    end
end
