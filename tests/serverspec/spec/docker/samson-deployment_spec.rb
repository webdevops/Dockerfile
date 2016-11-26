require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        @image = Docker::Image.build_from_dir('.', { 'dockerfile' => ENV['DOCKERFILE'] })
        set :docker_image, @image.id
    end

    include_examples 'bootstrap::layout'
    include_examples 'bootstrap::distribution'
    include_examples 'bootstrap::toolchain'

    include_examples 'collection::base-app'
    include_examples 'collection::samson-deployment::php'
    include_examples 'collection::samson-deployment'

end
