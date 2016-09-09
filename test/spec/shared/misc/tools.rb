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

shared_examples 'misc::pdflatex' do
    it "should include pdflatex" do
        expect(file("/usr/bin/pdflatex")).to be_executable
    end
end

shared_examples 'misc::letsencrypt' do
    it "should include letsencrypt" do
        expect(file("/usr/bin/letsencrypt")).to be_executable
    end
    it "should include certbot" do
        expect(file("/usr/bin/certbot")).to be_executable
    end
end