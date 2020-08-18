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

$testConfiguration[:php] = 7
$testConfiguration[:phpXdebug] = true
$testConfiguration[:phpApcu] = true
$testConfiguration[:phpRedis] = true
$testConfiguration[:phpBlackfire] = false
$testConfiguration[:phpOfficialImage] = false

if ((os[:family] == 'ubuntu' and os[:version] == '12.04') or
    (os[:family] == 'ubuntu' and os[:version] == '14.04') or
    (os[:family] == 'ubuntu' and os[:version] == '15.04') or
    (os[:family] == 'ubuntu' and os[:version] == '15.10') or
    (os[:family] == 'redhat' and os[:version] == '7') or
    (os[:family] == 'debian' and os[:version] == '7') or
    (os[:family] == 'debian' and os[:version] == '8') or
    (ENV['DOCKER_TAG'].match('php5')) or
    (ENV['DOCKER_TAG'].match('alpine-3')) or
    (ENV['DOCKER_TAG'] =~ /^5\.[0-9]+/)
   )
    $testConfiguration[:php] = 5
end

if ((ENV['DOCKER_TAG'].match('php7')) or
    (ENV['DOCKER_TAG'] =~ /^7\.[0-9]+/)
   )
    $testConfiguration[:php] = 7
end

if (ENV['DOCKER_TAG'] =~ /^8\.[0-9]+/)
    $testConfiguration[:php] = 8
end

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
