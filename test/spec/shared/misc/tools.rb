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


shared_examples 'misc::graphviz' do
    it "should include graphviz" do
        expect(file("/usr/bin/dot")).to be_executable
    end
end