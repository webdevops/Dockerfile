require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        image = Docker::Image.build_from_dir('.')
        set :docker_image, image.id
    end

    include_examples 'php::cli'
    include_examples 'php5::cli::version'
    include_examples 'php::modules'
    include_examples 'php5::modules'
    include_examples 'php::cli::test::sha1'
    include_examples 'php::composer'

    # services
    include_examples 'php-fpm::listening::public'

end
