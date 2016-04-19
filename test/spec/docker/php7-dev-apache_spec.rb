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
    include_examples 'collection::php7'
    include_examples 'collection::php-fpm7'
    include_examples 'collection::php-fpm7::local-only'
    include_examples 'collection::php-tools'
    include_examples 'collection::apache'
    include_examples 'collection::php-fpm7::webserver-test'

end
