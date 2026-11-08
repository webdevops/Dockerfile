#!/bin/bash
# Runs container-structure-test against every local webdevops image that has a suite in this directory.
# Exits non-zero if any image fails.

cd "$(dirname "$0")" || exit 1

FAILED=0
for testRepo in */; do
    testRepo="${testRepo%/}"
    if [[ ! -f "$testRepo/test.yaml" ]]; then
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
        CONFIGS=(--config "$testRepo/test.yaml")
        if [[ -f "$testRepo/$imageTag/test.yaml" ]]; then
            CONFIGS+=(--config "$testRepo/$imageTag/test.yaml")
        fi
        container-structure-test test --image "webdevops/$testRepo:$imageTag" "${CONFIGS[@]}" || FAILED=1
    done
done

exit $FAILED
