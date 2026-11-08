#!/bin/bash
# Runs container-structure-test against the local webdevops images that have a suite in this directory.
# Only the tags the CI builds are tested (the -t "webdevops/..." lines in .github/workflows/build.yaml);
# tags that are not built locally are skipped.
# Exits non-zero if a test fails, if docker cannot list the images or if no image was tested.

cd "$(dirname "$0")" || exit 1

BUILD_YAML="../../.github/workflows/build.yaml"

if [[ ! -f "$BUILD_YAML" ]]; then
    echo "ERROR: $BUILD_YAML not found" >&2
    exit 1
fi

if ! LOCAL_IMAGES="$(docker images --format '{{.Repository}}:{{.Tag}}')"; then
    echo "ERROR: docker images failed" >&2
    exit 1
fi

FAILED=0
TESTED=0

for testRepo in */; do
    testRepo="${testRepo%/}"

    if [[ ! -f "$testRepo/test.yaml" ]]; then
        continue
    fi

    BUILT_TAGS="$(grep -oE "\"webdevops/${testRepo}:[^\"]+\"" "$BUILD_YAML" | tr -d '"' | cut -d ':' -f 2 | sort -u)"

    for imageTag in ${BUILT_TAGS}; do
        if ! grep -qxF "webdevops/$testRepo:$imageTag" <<< "$LOCAL_IMAGES"; then
            continue
        fi

        echo "=============================================="
        echo "Testing: webdevops/$testRepo:$imageTag"
        echo "=============================================="

        CONFIGS=(--config "$testRepo/test.yaml")
        if [[ -f "$testRepo/$imageTag/test.yaml" ]]; then
            CONFIGS+=(--config "$testRepo/$imageTag/test.yaml")
        fi

        TESTED=$((TESTED + 1))
        container-structure-test test --image "webdevops/$testRepo:$imageTag" "${CONFIGS[@]}" || FAILED=1
    done
done

if [[ "$TESTED" -eq 0 ]]; then
    echo "ERROR: no image was tested. Build at least one tag of these images first:" $(ls -d */ | tr -d '/') >&2
    exit 1
fi

echo "Tested $TESTED images"
exit $FAILED
