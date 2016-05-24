#############################
# Vacuum database
#############################

if [[ -x "/opt/docker/bin/samson-cleanup-db.sh" ]]; then
    /opt/docker/bin/samson-cleanup-db.sh
fi
