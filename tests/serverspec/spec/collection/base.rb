shared_examples 'collection::base' do
    include_examples 'base::layout'
    include_examples 'base::packages'
    include_examples 'base::supervisor'
    include_examples 'base::toolchain'
end
