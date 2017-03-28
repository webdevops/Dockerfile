require 'serverspec'
require 'docker'
require 'spec_init'

describe "Dockerfile" do
    before(:all) do
        set :docker_image, ENV['DOCKERIMAGE_ID']
    end

    include_examples 'bootstrap::layout'
    include_examples 'bootstrap::distribution'

    include_examples 'collection::base-app'
    include_examples 'collection::samson-deployment::php'
    include_examples 'collection::samson-deployment'

end
