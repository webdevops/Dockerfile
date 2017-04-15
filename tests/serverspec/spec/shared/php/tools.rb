shared_examples 'php::tools' do

	[
        "/usr/local/bin/composer",
    ].each do |file|

    	describe file("#{file}") do 
    		it { should be_file }
    		it { should be_executable }
    	end

    	describe command('#{file}') do
	        its(:exit_status) { should eq 0 }
	    end
	    
    end
end
