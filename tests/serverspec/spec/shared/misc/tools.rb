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

shared_examples 'misc::file' do
    it "should include file" do
        expect(file("/usr/bin/file")).to be_executable
    end

    describe command('file --version') do
        its(:stdout) { should match %r!^file-5\.[0-9]+! }
        its(:exit_status) { should eq 0 }
    end

    # The magic database works: it tells a PHP script from a binary
    describe command("printf '<?php echo 1;\\n' > /tmp/file-test.php && file --brief --mime-type /tmp/file-test.php /usr/local/bin/php") do
        its(:stdout) { should eq "text/x-php\napplication/x-pie-executable\n" }
        its(:exit_status) { should eq 0 }
    end
end
