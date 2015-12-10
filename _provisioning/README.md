# Docker provisioning files

These are the base configuration files which will be deployed into the Dockerfile directories to simplify and 
minimize overhead for multiple docker containers (eg. multiple ubuntu and debian versions).
 
These files will be deployed based on [.bin/provision.sh](../.bin/provision.sh) rules. This script will just copy these
files into the configured (and filtered) docker directories.
