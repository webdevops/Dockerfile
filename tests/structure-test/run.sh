#!/bin/bash

TEST_REPOS="bootstrap base base-app php"

for testRepo in ${TEST_REPOS}; do
    if [[ ! -f $testRepo/test.yaml ]]; then
        echo "Skipping tests for webdevops/$testRepo as no common test.yaml was found"
        continue
    fi
    TEST_IMAGES="$(docker images -f "reference=webdevops/${testRepo}" --format "{{.Tag}}")"
    for imageTag in ${TEST_IMAGES}; do
        if [ "$imageTag" = "<none>" ]; then
            continue
        fi
        echo "=============================================="
        echo "Testing: webdevops/$testRepo:$imageTag"
        echo "=============================================="
        if [[ -f $testRepo/$imageTag/test.yaml ]]; then
            container-structure-test test --image webdevops/$testRepo:$imageTag --config $testRepo/$imageTag/test.yaml  --config $testRepo/test.yaml
        else
            container-structure-test test --image webdevops/$testRepo:$imageTag --config $testRepo/test.yaml
        fi
    done
done
