require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        @image = Docker::Image.build_from_dir('.', { 'dockerfile' => ENV['DOCKERFILE'] })
        set :docker_image, @image.id
    end

    include_examples 'collection::bootstrap'
    include_examples 'collection::varnish'

end
