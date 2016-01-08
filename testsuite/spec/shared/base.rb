shared_examples 'base::all' do
    include_examples 'base::layout'
    include_examples 'base::supervisor'
    include_examples 'base::syslog-ng'
    include_examples 'base::ansible'
    include_examples 'base::application-user'
end
