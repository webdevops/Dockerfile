#!/bin/bash

#
# Test webdevops/bootstrap images
#
BOOTSTRAP_IMAGES="$(docker images -f "reference=webdevops/bootstrap" --format "{{.Tag}}")"
for bootstrapImageTag in ${BOOTSTRAP_IMAGES}; do
    if [ "$bootstrapImageTag" = "<none>" ]; then
        continue
    fi
    echo "==================================="
    echo "Testing: webdevops/bootstrap:$bootstrapImageTag"
    echo "==================================="
    container-structure-test test --image webdevops/bootstrap:$bootstrapImageTag --config bootstrap/files.yaml
    if [[ -f bootstrap/$bootstrapImageTag/commands.yaml ]]; then
        container-structure-test test --image webdevops/bootstrap:$bootstrapImageTag --config bootstrap/$bootstrapImageTag/commands.yaml
    fi
done
