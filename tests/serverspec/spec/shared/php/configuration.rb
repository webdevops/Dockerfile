shared_examples 'php::cli::configuration' do
    describe 'PHP config parameters' do
        #####################
        # Resources
        #####################

        context php_config('memory_limit') do
            its(:value) { should eq '512M' }
        end

        context php_config('post_max_size') do
            its(:value) { should eq '50M' }
        end

        context php_config('upload_max_filesize') do
            its(:value) { should eq '50M' }
        end

        context php_config('max_input_vars') do
            its(:value) { should eq 5000 }
        end

        #####################
        # Settings
        #####################

        context php_config('variables_order') do
            its(:value) { should eq 'GPCS' }
        end

        context php_config('request_order') do
            its(:value) { should eq 'GP' }
        end

        context php_config('date.timezone') do
            its(:value) { should eq 'UTC' }
        end

        context php_config('allow_url_fopen') do
            its(:value) { should eq 1 }
        end

        context php_config('allow_url_include') do
            its(:value) { should eq '' }
        end

        context php_config('expose_php') do
            its(:value) { should eq '' }
        end

        context php_config('log_errors') do
            its(:value) { should eq 1 }
        end

        context php_config('open_basedir') do
            its(:value) { should eq '' }
        end

        context php_config('output_buffering') do
            its(:value) { should eq 0 }
        end

        context php_config('short_open_tag') do
            its(:value) { should eq '' }
        end
    end
end

shared_examples 'php::cli::configuration::production' do
    describe 'PHP config parameters' do
        ####################################
        # OPCODE CACHE
        ####################################
        context php_config('opcache.memory_consumption') do
            its(:value) { should eq 256 }
        end

        context php_config('opcache.interned_strings_buffer') do
            its(:value) { should eq 16 }
        end

        context php_config('opcache.max_accelerated_files') do
            its(:value) { should eq 7963 }
        end

        context php_config('opcache.fast_shutdown') do
            its(:value) { should eq 1 }
        end
    end
end


shared_examples 'php::cli::configuration::development' do
    describe 'PHP config parameters' do
        ####################################
        # XDEBUG
        ####################################
        context php_config('xdebug.remote_enable') do
            its(:value) { should eq 1 }
        end

        context php_config('xdebug.remote_connect_back') do
            its(:value) { should eq 1 }
        end

        ####################################
        # OPCODE CACHE
        ####################################
        context php_config('opcache.memory_consumption') do
            its(:value) { should eq 256 }
        end

        context php_config('opcache.revalidate_freq') do
            its(:value) { should eq 0 }
        end

        context php_config('opcache.validate_timestamps') do
            its(:value) { should eq 1 }
        end

        context php_config('opcache.interned_strings_buffer') do
            its(:value) { should eq 16 }
        end

        context php_config('opcache.max_accelerated_files') do
            its(:value) { should eq 7963 }
        end

        context php_config('opcache.fast_shutdown') do
            its(:value) { should eq 1 }
        end
    end
end
