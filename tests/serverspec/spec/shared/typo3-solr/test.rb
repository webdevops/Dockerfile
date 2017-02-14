shared_examples 'typo3-solr::test' do
    describe 'solr-system-status' do
        it 'solr version should be correct', :retry => 20, :retry_wait => 3 do
            content = get_url('http://localhost:8983/solr/admin/info/system?wt=json')
            content = JSON.parse(content)

            if content.key?('solr_home')
                expect(content['solr_home']).to eql('/opt/solr/server/solr')
            end

            expect(content['lucene']['solr-spec-version']).to eql(ENV['SOLR_VERSION'])
        end
    end

    describe 'solr-core-status' do
        it 'solr version should be correct', :retry => 20, :retry_wait => 3 do
            content = get_url('http://localhost:8983/solr/admin/cores?indexInfo=false&wt=json')
            content = JSON.parse(content)

            [
                "core_ar",
                "core_bg",
                "core_ca",
                "core_cs",
                "core_da",
                "core_de",
                "core_el",
                "core_en",
                "core_es",
                "core_eu",
                "core_fa",
                "core_fi",
                "core_fr",
                "core_gl",
                "core_hi",
                "core_hu",
                "core_hy",
                "core_id",
                # "core_ie",
                "core_it",
                "core_ja",
                "core_km",
                "core_ko",
                "core_lo",
                # "core_lv",
                "core_my",
                "core_nl",
                "core_no",
                "core_pl",
                "core_pt",
                "core_ptbr",
                "core_ro",
                # "core_rs",
                "core_ru",
                "core_sv",
                "core_th",
                "core_tr",
                "core_uk",
                "core_zh",
            ].each do |solr_core|
                expect(content['status'][solr_core]['name']).to eql(solr_core)
            end
        end
    end
end

