shared_examples 'collection::certbot' do
    include_examples 'python::toolchain'

    include_examples 'misc::letsencrypt'
    include_examples 'certbot::layout'
end
