shared_examples 'base::packages' do
    [
        'wget',
        'curl',
    ].each do |package|
        describe package("#{package}") do
            it { should be_installed }
        end
    end
end
