shared_examples 'collection::bootstrap' do
    include_examples 'base::distribution'
    include_examples 'base::ansible'
    include_examples 'base::locales'
end
