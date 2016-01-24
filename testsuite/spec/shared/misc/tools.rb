shared_examples 'misc::graphicsmagick' do
    it "should include graphicsmagick" do
        expect(file("/usr/bin/gm")).to be_executable
    end
end

shared_examples 'misc::imagemagick' do
    it "should include imagemagick" do
        expect(file("/usr/bin/convert")).to be_executable
    end
end
