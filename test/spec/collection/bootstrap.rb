shared_examples 'collection::bootstrap' do
    include_examples 'bootstrap::layout'
    include_examples 'bootstrap::distribution'
    include_examples 'bootstrap::toolchain'
    include_examples 'bootstrap::ansible'
    include_examples 'bootstrap::locales'
end
