shared_examples 'sphinx::sphinx' do
    describe command('sphinx-build --version') do
        its(:exit_status) { should eq 0 }
    end

    describe command('sphinx-apidoc --version') do
        its(:exit_status) { should eq 0 }
    end

    describe command('sphinx-quickstart --version') do
        its(:exit_status) { should eq 0 }
    end

    describe command('sphinx-autobuild -h') do
        its(:exit_status) { should eq 0 }
    end

    describe command('sphinx-autogen -h') do
        its(:exit_status) { should eq 0 }
    end
end
