require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        @image = Docker::Image.build_from_dir('.', { 'dockerfile' => $specConfiguration['DOCKERFILE'] })
        set :docker_image, @image.id
    end

    include_examples 'collection::bootstrap'
    include_examples 'collection::base'
    include_examples 'collection::base-app'

    if ($testConfiguration[:php] == 5)
        include_examples 'collection::php5::development'
        include_examples 'collection::php-fpm5'
        include_examples 'collection::php-fpm5::public'
    else
        include_examples 'collection::php7::development'
        include_examples 'collection::php-fpm7'
        include_examples 'collection::php-fpm7::public'
    end

    include_examples 'collection::php-tools'
    include_examples 'collection::nginx'

    if ($testConfiguration[:php] == 5)
        include_examples 'collection::php-fpm5::webserver-test::development'
    else
        include_examples 'collection::php-fpm7::webserver-test::development'
    end

end
