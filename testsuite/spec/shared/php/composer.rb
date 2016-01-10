shared_examples 'php::composer' do
    it "should composer be installed" do
        expect(file("/usr/local/bin/composer")).to be_file
        expect(file("/usr/local/bin/composer")).to be_executable
    end
end
