shared_examples 'misc::graphicsmagick' do
    it "should include graphicsmagick" do
        expect(file("/usr/bin/gm")).to be_executable
    end

    describe command('/usr/bin/gm -version') do
        its(:stdout) { should match %r!GraphicsMagick [0-9]+\.[0-9]+! }
        its(:exit_status) { should eq 0 }
    end
end

shared_examples 'misc::imagemagick' do
    it "should include imagemagick" do
        expect(file("/usr/bin/convert")).to be_executable
    end

    describe command('/usr/bin/convert --version') do
        its(:stdout) { should match %r!Version: ImageMagick! }
        its(:exit_status) { should eq 0 }
    end
end

shared_examples 'misc::ghostscript' do
    it "should include ghostscript" do
        expect(file("/usr/bin/gs")).to be_executable
    end

    describe command('/usr/bin/gs --version') do
        its(:stdout) { should match %r![0-9]+\.[0-9]+! }
        its(:exit_status) { should eq 0 }
    end
end

shared_examples 'misc::graphviz' do
    it "should include graphviz" do
        expect(file("/usr/bin/dot")).to be_executable
    end

    describe command('/usr/bin/dot -V') do
        its(:stderr) { should match %r!graphviz version! }
        its(:exit_status) { should eq 0 }
    end
end

shared_examples 'misc::letsencrypt' do
    it "should include certbot" do
        expect(file("/usr/bin/certbot")).to be_executable
    end

    describe command('/usr/bin/certbot --version') do
        its(:stderr) { should match %r!certbot! }
        its(:exit_status) { should eq 1 }
    end
end
