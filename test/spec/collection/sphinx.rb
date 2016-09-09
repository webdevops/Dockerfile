shared_examples 'collection::sphinx' do
    include_examples 'sphinx::sphinx'
    include_examples 'misc::graphviz'
    if ( $testConfiguration[:pdflatex] )
        include_examples 'misc::pdflatex'
    end
end
