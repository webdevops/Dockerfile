shared_examples 'apache::modules' do
    describe command('apachectl -t -D DUMP_MODULES') do
        its(:stdout) { should contain('core_module') }
        its(:stdout) { should contain('http_module') }

        its(:stdout) { should contain('so_module') }
        its(:stdout) { should contain('log_config_module') }
        its(:stdout) { should contain('logio_module') }
        its(:stdout) { should contain('version_module') }
        its(:stdout) { should contain('actions_module') }
        its(:stdout) { should contain('alias_module') }
        its(:stdout) { should contain('auth_basic_module') }
        its(:stdout) { should contain('authn_file_module') }
        its(:stdout) { should contain('authz_host_module') }
        its(:stdout) { should contain('autoindex_module') }
        its(:stdout) { should contain('deflate_module') }
        its(:stdout) { should contain('dir_module') }
        its(:stdout) { should contain('env_module') }
        its(:stdout) { should contain('headers_module') }
        its(:stdout) { should contain('mime_module') }
        its(:stdout) { should contain('negotiation_module') }
        its(:stdout) { should contain('rewrite_module') }
        its(:stdout) { should contain('setenvif_module') }
        its(:stdout) { should contain('ssl_module') }
        its(:stdout) { should contain('status_module') }
        its(:stdout) { should contain('expires_module') }

        ## mpm module
        if (os[:family] == 'debian' and os[:version] == '7') or (os[:family] == 'ubuntu' and os[:version] == '12.04')
            its(:stdout) { should contain('mpm_worker_module') }
        else
            its(:stdout) { should contain('mpm_event_module') }
        end

        ## fastcgi module
        if (os[:family] == 'debian' and os[:version] == '7') or
           (os[:family] == 'ubuntu' and os[:version] == '12.04') or
           (os[:family] == 'ubuntu' and os[:version] == '14.04')
            its(:stdout) { should contain('fastcgi_module') }
            its(:stdout) { should_not contain('proxy_module') }
            its(:stdout) { should_not contain('proxy_fcgi_module') }
        else
            its(:stdout) { should_not contain('fastcgi_module') }
            its(:stdout) { should contain('proxy_module') }
            its(:stdout) { should contain('proxy_fcgi_module') }
        end

        its(:exit_status) { should eq 0 }
    end
end
