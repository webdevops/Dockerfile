require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        @image = Docker::Image.build_from_dir('.', { 'dockerfile' => $specConfiguration['DOCKERFILE'] })
        set :docker_image, @image.id
    end

    include_examples 'collection::liquibase'

end
