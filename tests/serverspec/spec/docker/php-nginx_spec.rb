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
    include_examples 'collection::php8::production'
    include_examples 'collection::php-fpm8'
    include_examples 'collection::php-fpm8::local-only'

    include_examples 'collection::nginx'

    include_examples 'collection::php-fpm8::webserver-test::production'

end
