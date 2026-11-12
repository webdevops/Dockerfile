# Image tests: a command of its own, "image-test <arguments>", see EntrypointChecks
shift
echo "image-test as $(id -un): $*"
exit 0
