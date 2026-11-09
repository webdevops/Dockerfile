shared_examples 'base-app::moreutils' do
    describe command('command -v sponge'), :if => ['debian', 'ubuntu'].include?(os[:family]) do
        its(:stdout) { should eq "/usr/bin/sponge\n" }
        its(:exit_status) { should eq 0 }
    end
end
