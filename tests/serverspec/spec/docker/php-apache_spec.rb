require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        @image = Docker::Image.build_from_dir('.', { 'dockerfile' => ENV['DOCKERFILE'] })
        set :docker_image, @image.id
    end

    include_examples 'collection::bootstrap'
    include_examples 'collection::base'
    include_examples 'collection::base-app'

    if ($testConfiguration[:php] == 5)
        include_examples 'collection::php5::production'
        include_examples 'collection::php-fpm5'
        include_examples 'collection::php-fpm5::local-only'
    else
        include_examples 'collection::php7::production'
        include_examples 'collection::php-fpm7'
        include_examples 'collection::php-fpm7::local-only'
    end

    include_examples 'collection::apache'

    if ($testConfiguration[:php] == 5)
        include_examples 'collection::php-fpm5::webserver-test::production'
    else
        include_examples 'collection::php-fpm7::webserver-test::production'
    end
end
