shared_examples 'collection::bootstrap' do
    include_examples 'bootstrap::layout'
    include_examples 'bootstrap::distribution'
    include_examples 'bootstrap::toolchain'

    if (os[:family] == 'alpine')
        include_examples 'vendor::alpine::apk'
    end
end

shared_examples 'collection::bootstrap::upstream-image' do
    include_examples 'bootstrap::distribution'

    if (os[:family] == 'alpine')
        include_examples 'vendor::alpine::apk'
    end
end
