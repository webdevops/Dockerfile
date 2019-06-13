#!/bin/bash

set -o pipefail
set -o errtrace
set -o nounset
set -o errexit

LIQUIBASE_OPTS="$LIQUIBASE_OPTS --defaultsFile=/liquibase.properties"

echo -n > /liquibase.properties

## Properties file
if [[ -f liquibase.properties ]]; then
    cat liquibase.properties >> /liquibase.properties
fi

## Database driver
if [[ -n "$LIQUIBASE_DRIVER" ]]; then
    sed -i '/^driver:/d' /liquibase.properties
    echo "driver: ${LIQUIBASE_DRIVER}" >> /liquibase.properties
fi

## Classpath
if [[ -n "$LIQUIBASE_CLASSPATH" ]]; then
    echo "classpath: ${LIQUIBASE_CLASSPATH}" >> /liquibase.properties
fi

## Database url
if [[ -n "$LIQUIBASE_URL" ]]; then
    echo "url: ${LIQUIBASE_URL}" >> /liquibase.properties
fi

## Database username
if [[ -n "$LIQUIBASE_USERNAME" ]]; then
    echo "username: ${LIQUIBASE_USERNAME}" >> /liquibase.properties
fi

## Database password
if [[ -n "$LIQUIBASE_PASSWORD" ]]; then
    echo "password: ${LIQUIBASE_PASSWORD}" >> /liquibase.properties
fi

## Database contexts
if [[ -n "$LIQUIBASE_CONTEXTS" ]]; then
    echo "contexts: ${LIQUIBASE_CONTEXTS}" >> /liquibase.properties
fi

## Database changelog file
if [[ -n "$LIQUIBASE_CHANGELOG" ]]; then
    if ! grep -q '^changeLogFile' /liquibase.properties; then
        echo "changeLogFile: ${LIQUIBASE_CHANGELOG}" >> /liquibase.properties
    fi
fi

function executeLiquibase() {
    exec /opt/liquibase/liquibase $LIQUIBASE_OPTS "$@"
}


if [[ "$#" -ge 1 ]]; then
    TASK="$1"
    shift

    case "$TASK" in
            ## Custom liquibase command
        liquibase)
            executeLiquibase "$@"
            ;;

            ## Database Update Commands
        update|updateCount|updateSQL|updateCountSQL) ;&
            ## Database Rollback Commands
        rollback|rollbackToDate|rollbackCount|rollbackSQL|rollbackToDateSQL|rollbackCountSQL|updateTestingRollback|generateChangeLog) ;&
            ## Diff Commands
        diff|diffChangeLog) ;&
            ## Documentation Commands
        dbDoc) ;&
            ## Maintenance Commands
        status|validate|changelogSync|changelogSyncSQL|markNextChangeSetRan|listLocks|releaseLocks|dropAll|clearCheckSums)
            if [[ "$#" -eq 0 ]]; then
                executeLiquibase "$TASK"
            else
                executeLiquibase "$TASK" "$@"
            fi
            ;;

            ## show configuration
        showConf)
            cat /liquibase.properties
            ;;

            ## Help
        help)
            cat <<EOF
Database Update Commands
-------------------------------------------------------------------------------
    update                          Updates database to current version.
    updateCount <value>             Applies the next <value> change sets.
    updateSQL                       Writes SQL to update database to current
                                    version to STDOUT.
    updateCountSQL <value>          Writes SQL to apply the next <value>
                                    change sets to STDOUT.

Database Rollback Commands
-------------------------------------------------------------------------------
    rollback <tag>                  Rolls back the database to the state it
                                    was in when the tag was applied.
    rollbackToDate <date/time>      Rolls back the database to the state it
                                    was in at the given date/time.
    rollbackCount <value>           Rolls back the last <value> change sets.
    rollbackSQL <tag>               Writes SQL to roll back the database to
                                    the state it was in when the tag was
                                    applied to STDOUT.
    rollbackToDateSQL <date/time>   Writes SQL to roll back the database to
                                    the state it was in at the given date/time
                                    version to STDOUT.
    rollbackCountSQL <value>        Writes SQL to roll back the last <value>
                                    change sets to STDOUT.
    futureRollbackSQL               Writes SQL to roll back the database to
                                    the current state after the changes in
                                    the changeslog have been applied.
    updateTestingRollback           Updates the database, then rolls back
                                    changes before updating again.
    generateChangeLog               generateChangeLog of the database to
                                    standard out. v1.8 requires the dataDir
                                    parameter currently.

Diff Commands
-------------------------------------------------------------------------------
    diff [diff parameters]          Writes description of differences to
                                    standard out.
    diffChangeLog [diff parameters] Writes Change Log XML to update the base
                                    database to the target database to
                                    standard out.

Documentation Commands
-------------------------------------------------------------------------------
    dbDoc <outputDirectory>         Generates Javadoc-like documentation based
                                    on current database and change log.

Maintenance Commands
-------------------------------------------------------------------------------
    tag <tag>                       "Tags" the current database state for
                                    future rollback.
    tagExists <tag>                 Checks whether the given tag is already
                                    existing.
    status                          Outputs count (list if --verbose) of unrun
                                    change sets.
    validate                        Checks the changelog for errors.
    changelogSync                   Mark all changes as executed in the
                                    database.
    changelogSyncSQL                Writes SQL to mark all changes as executed
                                    in the database to STDOUT.
    markNextChangeSetRan            Mark the next change set as executed in
                                    the database.
    listLocks                       Lists who currently has locks on the
                                    database changelog.
    releaseLocks                    Releases all locks on the database
                                    changelog.
    dropAll                         Drops all database objects owned by the
                                    user. Note that functions, procedures
                                    and packages are not dropped
                                    (limitation in 1.8.1).
    clearCheckSums                  Removes current checksums from database.
                                    On next run checksums will be recomputed.
EOF
            exit 1
            ;;

            ## Default task (eg. sh, bash)
        *)
            exec "$TASK" "$@"
            ;;
    esac
fi
