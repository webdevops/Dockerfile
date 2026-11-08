#!/usr/bin/env bash
# Runs container-structure-test against the local webdevops images that have a suite in this directory.
# Only the tags the CI builds are tested (the -t "webdevops/..." lines in .github/workflows/build.yaml);
# tags that are not built locally are skipped, and a tag that is the same image as one already tested
# with the same suites (for example latest and ubuntu-22.04) is tested once.
# Exits non-zero if a test fails, if docker cannot list the images or if no image was tested.
# Needs Bash 4 or newer (declare -A, mapfile).

if (( BASH_VERSINFO[0] < 4 )); then
    echo "ERROR: run.sh needs Bash 4 or newer, this is Bash $BASH_VERSION." >&2
    echo "Run make structure-test inside webdevops/dockerfile-build-env, or put a newer bash first in PATH." >&2
    exit 1
fi

cd "$(dirname "$0")" || exit 1

BUILD_YAML="../../.github/workflows/build.yaml"

if [[ ! -f "$BUILD_YAML" ]]; then
    echo "ERROR: $BUILD_YAML not found" >&2
    exit 1
fi

# "<repository>:<tag> <image id>" per line
if ! LOCAL_IMAGES="$(docker images --no-trunc --format '{{.Repository}}:{{.Tag}} {{.ID}}')"; then
    echo "ERROR: docker images failed" >&2
    exit 1
fi

FAILED=0
TESTED=0
SUITES=()
declare -A TESTED_AS

for testRepo in */; do
    testRepo="${testRepo%/}"

    if [[ ! -f "$testRepo/test.yaml" ]]; then
        continue
    fi
    SUITES+=("$testRepo")

    mapfile -t BUILT_TAGS < <(grep -oE "\"webdevops/${testRepo}:[^\"]+\"" "$BUILD_YAML" | tr -d '"' | cut -d ':' -f 2 | sort -u)

    for imageTag in "${BUILT_TAGS[@]}"; do
        image="webdevops/$testRepo:$imageTag"
        imageId="$(awk -v image="$image" '$1 == image { print $2; exit }' <<< "$LOCAL_IMAGES")"
        if [[ -z "$imageId" ]]; then
            continue
        fi

        CONFIGS=(--config "$testRepo/test.yaml")
        if [[ -f "$testRepo/$imageTag/test.yaml" ]]; then
            CONFIGS+=(--config "$testRepo/$imageTag/test.yaml")
        fi

        testKey="$imageId ${CONFIGS[*]}"
        if [[ -n "${TESTED_AS[$testKey]:-}" ]]; then
            echo "Skipping $image: same image and suites as ${TESTED_AS[$testKey]}"
            continue
        fi
        TESTED_AS[$testKey]="$image"

        echo "=============================================="
        echo "Testing: $image"
        echo "=============================================="

        TESTED=$((TESTED + 1))
        container-structure-test test --image "$image" "${CONFIGS[@]}" || FAILED=1
    done
done

if [[ "$TESTED" -eq 0 ]]; then
    echo "ERROR: no image was tested. Build at least one tag of these images first: ${SUITES[*]}" >&2
    exit 1
fi

echo "Tested $TESTED images"
exit $FAILED
