require 'serverspec'
require 'docker'
require 'spec_init'

describe "Dockerfile" do
    before(:all) do
        set :docker_image, ENV['DOCKERIMAGE_ID']
    end

    include_examples 'collection::bootstrap'
    include_examples 'collection::base'
    include_examples 'collection::base-app'
    include_examples 'php::modules::ftp_ssl'
    include_examples 'collection::php8::development'
    include_examples 'collection::php-fpm8'
    include_examples 'collection::php-fpm8::public'

    include_examples 'collection::php-tools'
    include_examples 'collection::development'

end
