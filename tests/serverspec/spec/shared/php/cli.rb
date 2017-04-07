shared_examples 'php::cli' do
    it "should include php cli" do
        expect(file("php")).to be_executable
    end

    describe command('php -i') do
        its(:exit_status) { should eq 0 }
    end
end
