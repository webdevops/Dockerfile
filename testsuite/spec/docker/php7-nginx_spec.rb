require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        image = Docker::Image.build_from_dir('.')
        set :docker_image, image.id
    end

    include_examples 'php::modules'
    include_examples 'php7::modules'
    include_examples 'php::composer'

    # services
    include_examples 'php-fpm::listening::local-only'
    include_examples 'nginx::listening::public'

end
