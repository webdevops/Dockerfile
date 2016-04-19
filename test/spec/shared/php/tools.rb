shared_examples 'php::tools' do

	[
        "/usr/local/bin/composer",
        "/usr/local/bin/phploc",
        "/usr/local/bin/pdepend",
        "/usr/local/bin/phpmd",
        "/usr/local/bin/phpcs",
        "/usr/local/bin/phpcbf",
        "/usr/local/bin/phpcpd",
        "/usr/local/bin/phpdcd",
        "/usr/local/bin/phpmetrics",
        "/usr/local/bin/php-cs-fixer",
        "/usr/local/bin/deprecation-detector",
        "/usr/local/bin/php7cc",
        "/usr/local/bin/phpunit",
        "/usr/local/bin/phpspec"

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
