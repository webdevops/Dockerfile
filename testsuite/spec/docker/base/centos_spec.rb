require 'serverspec'
require 'docker'
require 'spec_helper'

describe "Dockerfile" do
    before(:all) do
        image = Docker::Image.build_from_dir('.')

        set :os, family: :redhat
        set :backend, :docker
        set :docker_image, image.id
    end

    include_examples 'base::layout'
    include_examples 'base::supervisor'
    include_examples 'base::syslog-ng'
    include_examples 'base::ansible'
    include_examples 'base::application-user'
    include_examples 'base::localscripts'

end
