# -----------------------------------------------
# RSpec/Serverspec configuration
# -----------------------------------------------

RSpec.configure do |config|
    config.fail_fast = 3

    # show retry status in spec process
    config.verbose_retry = true

    # show exception that triggers a retry if verbose_retry is set to true
    config.display_try_failure_messages = true
end

set :backend, :docker
set :docker_container, ENV['DOCKER_IMAGE']
set :os, :family => ENV['OS_FAMILY'], :version => ENV['OS_VERSION'], :arch => 'x86_64'

Excon.defaults[:write_timeout] = 1000
Excon.defaults[:read_timeout] = 1000

# -----------------------------------------------
# General spec configuration
# -----------------------------------------------

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
$testConfiguration[:phpBlackfire] = false
$testConfiguration[:phpOfficialImage] = false

if ENV['PHP_OFFICIAL'] and ENV['PHP_OFFICIAL'] == "1"
    $testConfiguration[:phpOfficialImage] = true
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

if ENV['PHP_BLACKFIRE'] and ENV['PHP_BLACKFIRE'] == "1"
    $testConfiguration[:phpBlackfire] = true
    $testConfiguration[:phpXdebug] = false
end
