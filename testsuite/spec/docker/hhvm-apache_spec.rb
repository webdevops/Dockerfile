require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        image = Docker::Image.build_from_dir('.')
        set :docker_image, image.id
    end

    include_examples 'php::cli'
    include_examples 'hhvm::cli::version'
    include_examples 'php::cli::test::sha1'
    include_examples 'php::composer'

    # services
    include_examples 'hhvm::listening::public'
    include_examples 'apache::listening::public'

    # test after services are up
    include_examples 'apache::modules'
    include_examples 'php::fpm::test::sha1'

end
