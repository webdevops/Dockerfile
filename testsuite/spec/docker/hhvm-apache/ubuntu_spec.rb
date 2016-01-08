require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        image = Docker::Image.build_from_dir('.')

        set :os, family: :ubuntu
        set :backend, :docker
        set :docker_image, image.id
    end

    # services
    include_examples 'hhvm::listening::public'
    include_examples 'apache::listening::public'

end
