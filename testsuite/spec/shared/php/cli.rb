shared_examples 'php::cli' do
    it "should include php cli" do
        expect(file("/usr/bin/php")).to be_executable
    end
end
