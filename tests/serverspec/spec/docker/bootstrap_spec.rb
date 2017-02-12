require 'serverspec'
require 'docker'
require 'spec_init'

describe "Dockerfile" do
    before(:all) do
        set :docker_image, ENV['DOCKERIMAGE_ID']
    end

    include_examples 'collection::bootstrap'

end
