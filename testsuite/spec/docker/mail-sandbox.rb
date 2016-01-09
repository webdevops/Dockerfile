require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        image = Docker::Image.build_from_dir('.')
        set :docker_image, image.id
    end

    # services
    include_examples 'postfix::listening::public'
    include_examples 'dovecot::listening::public'

end
