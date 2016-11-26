shared_examples 'samson-deployment::deployment' do

    describe command('/usr/local/bin/dep') do
        its(:exit_status) { should eq 0 }
    end

    describe command('/usr/local/bundle/bin/cap -v') do
        its(:exit_status) { should eq 0 }
    end

end
