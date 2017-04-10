require 'json'
require 'shellwords'
require 'serverspec'
require 'rspec/retry'
require 'spec_config'
require 'spec_autoload'
require 'spec_helper'

print "Serverspec configuration\n"
print "------------------------\n"
print " DOCKERIMAGE_ID: " + ENV['DOCKERIMAGE_ID'] + "\n"
print " DOCKER_IMAGE: " + ENV['DOCKER_IMAGE'] + "\n"
print " DOCKER_TAG: " + ENV['DOCKER_TAG'] + "\n"
print " OS_FAMILY: " + ENV['OS_FAMILY'] + "\n"
print " OS_VERSION: " + ENV['OS_VERSION'] + "\n"
print "\n"
print "--- internal config -----\n"
$testConfiguration.each {|key, value| puts " #{key}: #{value}" }
print "\n"


