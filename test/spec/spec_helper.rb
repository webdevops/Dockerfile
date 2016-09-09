require 'serverspec'

base_spec_dir = Pathname.new(File.join(File.dirname(__FILE__)))

Dir[base_spec_dir.join('shared/**/*.rb')].sort.each{ |f| require f }
Dir[base_spec_dir.join('collection/**.rb')].sort.each{ |f| require f }

set :backend, :docker
set :docker_container, ENV['DOCKER_IMAGE']
set :os, :family => ENV['OS_FAMILY'], :version => ENV['OS_VERSION'], :arch => 'x86_64'

Excon.defaults[:write_timeout] = 1000
Excon.defaults[:read_timeout] = 1000

$packageVersions = {}
$packageVersions[:ansible]         = %r!ansible 2.([0-9]\.?)+!
$packageVersions[:ansiblePlaybook] = %r!ansible-playbook 2.([0-9]\.?)+!

$testConfiguration = {}

if ['redhat', 'alpine'].include?(os[:family])
    $testConfiguration[:ansiblePath] = "/usr/bin"
else
    $testConfiguration[:ansiblePath] = "/usr/local/bin"
end

$testConfiguration[:phpXdebug] = true
$testConfiguration[:phpApcu] = true
$testConfiguration[:phpRedis] = true
$testConfiguration[:phpMhash] = true
$testConfiguration[:phpBlackfire] = false

$testConfiguration[:pdflatex] =  false

if ENV['PHP_XDEBUG'] and ENV['PHP_XDEBUG'] == "0"
    $testConfiguration[:phpXdebug] = false
end

if ENV['PHP_APCU'] and ENV['PHP_APCU'] == "0"
    $testConfiguration[:phpApcu] = false
end

if ENV['PHP_REDIS'] and ENV['PHP_REDIS'] == "0"
    $testConfiguration[:phpRedis] = false
end

if ENV['PHP_MHASH'] and ENV['PHP_MHASH'] == "0"
    $testConfiguration[:phpMhash] = false
end

if ENV['PHP_BLACKFIRE'] and ENV['PHP_BLACKFIRE'] == "1"
    $testConfiguration[:phpBlackfire] = true
    $testConfiguration[:phpXdebug] = false
end

if ENV['DOCKER_IMAGE'] == "webdevops/sphinx:tex"
     $testConfiguration[:pdflatex] =  true
end

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
