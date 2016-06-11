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

shared_examples 'misc::node' do
    it "should include node" do
        expect(file("/usr/local/bin/node")).to be_executable
    end
    it "should include npm" do
        expect(file("/usr/local/bin/npm")).to be_executable
    end
end

shared_examples 'misc::phantomjs' do
    it "should include phantomjs" do
        expect(file("/usr/local/bin/phantomjs")).to be_executable
    end
end