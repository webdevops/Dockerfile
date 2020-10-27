shared_examples 'php::composer' do
    it "should composer V1 be installed" do
        expect(file("/usr/local/bin/composer1")).to be_file
        expect(file("/usr/local/bin/composer1")).to be_executable
    end

    it "should composer V2 be installed" do
        expect(file("/usr/local/bin/composer2")).to be_file
        expect(file("/usr/local/bin/composer2")).to be_executable
    end

    describe command('/usr/local/bin/composer') do
        its(:exit_status) { should eq 0 }
    end
end
