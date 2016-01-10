require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        image = Docker::Image.build_from_dir('.')
        set :docker_image, image.id
    end

    # services
    include_examples 'apache::listening::public'

    # test after services are up
    include_examples 'apache::modules'
    include_examples 'apache::service::running'

end
