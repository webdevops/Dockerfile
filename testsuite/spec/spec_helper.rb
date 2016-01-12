require 'serverspec'

base_spec_dir = Pathname.new(File.join(File.dirname(__FILE__)))

Dir[base_spec_dir.join('shared/**/*.rb')].sort.each{ |f| require f }
Dir[base_spec_dir.join('collection/**.rb')].sort.each{ |f| require f }

set :backend, :docker
set :docker_container, ENV['DOCKER_IMAGE']
set :os, :family => ENV['OS_FAMILY'], :version => ENV['OS_VERSION'], :arch => 'x86_64'

def wait_retry(time, increment = 1, elapsed_time = 0, &block)
  begin
    yield
  rescue Exception => e
    if elapsed_time >= time
      raise e
    else
      sleep increment
      wait_retry(time, increment, elapsed_time + increment, &block)
    end
  end
end
