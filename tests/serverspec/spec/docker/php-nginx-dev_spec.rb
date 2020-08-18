require 'serverspec'
require 'docker'
require 'spec_init'

describe "Dockerfile" do
    before(:all) do
        set :docker_image, ENV['DOCKERIMAGE_ID']
    end

    include_examples 'collection::bootstrap'
    include_examples 'collection::base'
    include_examples 'collection::base-app'

    if ($testConfiguration[:php] == 5)
        include_examples 'collection::php5::development'
        include_examples 'collection::php-fpm5'
        include_examples 'collection::php-fpm5::public'
    elsif ($testConfiguration[:php] == 8)
    else
        include_examples 'collection::php7::development'
        include_examples 'collection::php-fpm7'
        include_examples 'collection::php-fpm7::public'
    end

    include_examples 'collection::php-tools'
    include_examples 'collection::nginx'

    if ($testConfiguration[:php] == 5)
        include_examples 'collection::php-fpm5::webserver-test::development'
    elsif ($testConfiguration[:php] == 8)
    else
        include_examples 'collection::php-fpm7::webserver-test::development'
    end

end
