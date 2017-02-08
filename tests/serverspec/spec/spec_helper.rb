require 'serverspec'
require 'rspec/retry'
require 'json'
require 'base64'

base_spec_dir = Pathname.new(File.join(File.dirname(__FILE__)))

Dir[base_spec_dir.join('shared/**/*.rb')].sort.each{ |f| require f }
Dir[base_spec_dir.join('collection/**.rb')].sort.each{ |f| require f }

$specConfiguration = ENV

set :backend, :docker
set :docker_container, $specConfiguration['DOCKER_IMAGE']
set :os, :family => $specConfiguration['OS_FAMILY'], :version => $specConfiguration['OS_VERSION'], :arch => 'x86_64'

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
    ($specConfiguration['DOCKER_TAG'].match('php5')))
    $testConfiguration[:php] = 5
end

if ($specConfiguration['DOCKER_TAG'].match('php7'))
    $testConfiguration[:php] = 7
end


if $specConfiguration['PHP_XDEBUG'] and $specConfiguration['PHP_XDEBUG'] == "0"
    $testConfiguration[:phpXdebug] = false
end

if $specConfiguration['PHP_APCU'] and $specConfiguration['PHP_APCU'] == "0"
    $testConfiguration[:phpApcu] = false
end

if $specConfiguration['PHP_REDIS'] and $specConfiguration['PHP_REDIS'] == "0"
    $testConfiguration[:phpRedis] = false
end

if $specConfiguration['PHP_MHASH'] and $specConfiguration['PHP_MHASH'] == "0"
    $testConfiguration[:phpMhash] = false
end

if $specConfiguration['PHP_BLACKFIRE'] and $specConfiguration['PHP_BLACKFIRE'] == "1"
    $testConfiguration[:phpBlackfire] = true
    $testConfiguration[:phpXdebug] = false
end
