shared_examples 'base::localscripts' do
    it "should docker scripts be installed" do
        expect(file("/usr/local/bin/apt-install")).to be_executable
        expect(file("/usr/local/bin/apt-upgrade")).to be_executable
        expect(file("/usr/local/bin/yum-install")).to be_executable
        expect(file("/usr/local/bin/yum-upgrade")).to be_executable
    end
end
