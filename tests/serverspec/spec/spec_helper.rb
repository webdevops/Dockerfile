require 'serverspec'

base_spec_dir = Pathname.new(File.join(File.dirname(__FILE__)))

Dir[base_spec_dir.join('shared/**/*.rb')].sort.each{ |f| require f }
Dir[base_spec_dir.join('collection/**.rb')].sort.each{ |f| require f }

set :backend, :docker
set :docker_container, ENV['DOCKER_IMAGE']
set :os, :family => ENV['OS_FAMILY'], :version => ENV['OS_VERSION'], :arch => 'x86_64'


Excon.defaults[:write_timeout] = 1000
Excon.defaults[:read_timeout] = 1000

$dockerInfo = {}
$dockerInfo[:image] = ENV['DOCKER_IMAGE']
$dockerInfo[:tag] = ENV['DOCKER_TAG']
$dockerInfo[:dockerfile] = ENV['DOCKERFILE']

$packageVersions = {}
$packageVersions[:ansible]         = %r!ansible 2.([0-9]\.?)+!
$packageVersions[:ansiblePlaybook] = %r!ansible-playbook 2.([0-9]\.?)+!

$testConfiguration = {}

if ['redhat', 'alpine'].include?(os[:family])
    $testConfiguration[:ansiblePath] = "/usr/bin"
else
    $testConfiguration[:ansiblePath] = "/usr/local/bin"
end

$testConfiguration[:php] = 7
$testConfiguration[:phpXdebug] = true
$testConfiguration[:phpApcu] = true
$testConfiguration[:phpRedis] = true
$testConfiguration[:phpMhash] = true
$testConfiguration[:phpBlackfire] = false

if ((os[:family] == 'ubuntu' and os[:version] == '12.04') or
    (os[:family] == 'ubuntu' and os[:version] == '14.04') or
    (os[:family] == 'ubuntu' and os[:version] == '15.04') or
    (os[:family] == 'ubuntu' and os[:version] == '15.10') or
    (os[:family] == 'redhat' and os[:version] == '7') or
    (os[:family] == 'debian' and os[:version] == '7') or
    (os[:family] == 'debian' and os[:version] == '8') or
    (os[:family] == 'alpine' and os[:version] == '3') or
    ($dockerInfo[:tag].match('php5')))
    $testConfiguration[:php] = 5
end

if ($dockerInfo[:tag].match('php7'))
    $testConfiguration[:php] = 7
end


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
