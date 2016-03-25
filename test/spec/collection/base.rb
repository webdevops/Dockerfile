shared_examples 'collection::base' do
    include_examples 'base::layout'
    include_examples 'base::packages'
    include_examples 'base::supervisor'
    include_examples 'base::syslog-ng'
    include_examples 'base::application-user'
end

shared_examples 'collection::base::without-application' do
    include_examples 'base::layout'
    include_examples 'base::packages'
    include_examples 'base::supervisor'
    include_examples 'base::syslog-ng'
end
