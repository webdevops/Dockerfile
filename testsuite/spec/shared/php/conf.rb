shared_examples 'php::cli::configuration' do
    describe 'PHP config parameters' do
        #####################
        # Resources
        #####################

        context  php_config('memory_limit') do
            its(:value) { should eq '512M' }
        end

        context php_config('post_max_size') do
            its(:value) { should eq '50M' }
        end

        context php_config('upload_max_filesize') do
            its(:value) { should eq '50M' }
        end

        #####################
        # Settings
        #####################

        context  php_config('date.timezone') do
            its(:value) { should eq 'UTC' }
        end

        context  php_config('allow_url_fopen') do
            its(:value) { should eq 1 }
        end

        context  php_config('allow_url_include') do
            its(:value) { should eq '' }
        end

        context  php_config('expose_php') do
            its(:value) { should eq '' }
        end

        context  php_config('log_errors') do
            its(:value) { should eq 1 }
        end

        context  php_config('open_basedir') do
            its(:value) { should eq '' }
        end

        context  php_config('output_buffering') do
            its(:value) { should eq 0 }
        end

        context  php_config('short_open_tag') do
            its(:value) { should eq '' }
        end
    end
end
