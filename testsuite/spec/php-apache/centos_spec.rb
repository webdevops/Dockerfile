# spec/Dockerfile_spec.rb

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

    include_examples 'php::fpm::private'
    include_examples 'apache::listening'

end
