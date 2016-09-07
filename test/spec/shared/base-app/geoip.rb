shared_examples 'base-app::geoip' do
    #########################
    ## Directories
    #########################
    [
        "/usr/share/GeoIP/",
    ].each do |file|
        describe file("#{file}") do
            # Type check
            it { should be_directory }

            # Owner test
            it { should be_owned_by 'root' }
            it { should be_grouped_into 'root' }

            # Read test
            it { should be_readable.by('owner') }
            it { should be_readable.by('group') }
            it { should be_readable.by('others') }

            # Write test
            it { should be_writable.by('owner') }
            it { should_not be_writable.by('group') }
            it { should_not be_writable.by('others') }

            # Exectuable test
            it { should be_executable.by('owner') }
            it { should be_executable.by('group') }
            it { should be_executable.by('others') }
        end
    end

    #########################
    ## Files
    #########################
    [
        "GeoLite2-City.mmdb",
        "GeoLite2-Country.mmdb",
        "GeoIP.dat",
        "GeoIPv6.dat",
        "GeoLiteCity.dat",
        "GeoLiteCityv6.dat",
        "GeoIPASNum.dat",
        "GeoIPASNumv6.dat",
    ].each do |file|
        describe file("/usr/share/GeoIP/#{file}") do
            # Type check
            it { should be_file }

            # Owner test
            it { should be_owned_by 'root' }
            it { should be_grouped_into 'root' }

            # Read test
            it { should be_readable.by('owner') }
            it { should be_readable.by('group') }
            it { should be_readable.by('others') }

            # Write test
            it { should_not be_writable.by('owner') }
            it { should_not be_writable.by('group') }
            it { should_not be_writable.by('others') }

            # Exectuable test
            it { should_not be_executable.by('owner') }
            it { should_not be_executable.by('group') }
            it { should_not be_executable.by('others') }
        end
    end
end
