require 'serverspec'
require 'docker'

set :backend, :exec
set :docker_container, ENV['DOCKER_IMAGE']

describe docker_image(ENV['DOCKER_IMAGE']) do
  its(:inspection) { should_not include 'Architecture' => 'i386' }
  its(:inspection) { should     include 'Architecture' => 'amd64' }
end
