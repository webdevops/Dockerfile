#!/usr/bin/env python

import os
import argparse
import json
import sys
from string import Template
from subprocess import call
import tempfile
import time

STORAGE = '/opt/docker/etc/.registry/provision.json'
PROVISION_DIR = '/opt/docker/provision/'

PLAYBOOK_TAGS=['bootstrap', 'build', 'onbuild', 'entrypoint']

PLAYBOOK = Template(
"""---

- hosts: all
  vars_files:
    - ./variables-webdevops.yml
    - ./variables.yml
  roles:
    - $roles
""")



def readJson():
    ret = {}

    # create registry directory if it doesn't exists
    if not os.path.exists(os.path.dirname(STORAGE)):
        os.mkdir(os.path.dirname(STORAGE))

    # try to read file
    if os.path.isfile(STORAGE):
        f=open(STORAGE).read()
        ret = json.loads(f)

    return ret



def saveJson(data):
    with open(STORAGE, 'w') as f:
        json.dump(data, f)



def buildRoleList(tags):
    json = readJson()
    roleList = {}

    # fetch roles list for each tag
    for tag in tags:
        if tag in json:
            for role in json[tag]:
                roleRow = json[tag][role]
                if role not in roleList:
                    roleList[role] = {}

                if 'tags' not in roleList[role]:
                    roleList[role]['tags'] = {}

                roleList[role]['role'] = role
                roleList[role]['added'] = roleRow['added']
                roleList[role]['priority'] = roleRow['priority']
                roleList[role]['tags'][tag] = tag

    return roleList


def buildSortedRoleList(tags):
    roleList = buildRoleList(tags)

    # sort list
    roleList = sorted(roleList, key=lambda x: (roleList[x]['priority'], roleList[x]['added']))

    return roleList



def buildPlaybook(roleList):
    ## build playbook
    ret = PLAYBOOK.substitute(
        roles = "\n    - ".join(roleList)
    )

    return ret


def buildPlaybookFromArgs(args):
    roleList = []

    ## add roles from tag (if use registry is active)
    if args.useRegistry and args.tags:
        roleList.extend(buildSortedRoleList(args.tags))

    ## add roles from command arguments
    if args.roles:
        for role in args.roles:
            roleList.extend(role.split(','))

    if roleList:
        return buildPlaybook(roleList)
    else:
        return False



def actionRun(args):
    if args.playbook:
        ## predefined playbook
        playbook = args.playbook
    else:
        ## dynamic playbook
        playbookContent = buildPlaybookFromArgs(args)

        if playbookContent:
            f = tempfile.NamedTemporaryFile(dir=PROVISION_DIR, prefix='playbook.', suffix='.yml', delete=False)
            f.write(playbookContent)
            f.close()
            playbook = f.name
        else:
            ## nothing to do
            sys.exit(0)

    ## build ansible command with args
    cmd = [
        'ansible-playbook',
        playbook,
        '-i', 'localhost,',
        '--connection=local',
    ]

    if args.tags:
        cmd.extend([
            '--tags=' + ','.join(args.tags)
         ])

    if args.args:
        cmd.extend(args.args)

    ## run ansible
    retval = call(cmd)

    ## cleanup dynamic playbook
    if not args.playbook:
        os.unlink(playbook)

    sys.exit(retval)



def actionPlaybook(args):
    playbook = buildPlaybookFromArgs(args)

    if playbook:
        print playbook
    else:
        sys.exit(1)



def actionList(args):
    json = readJson()
    list = {}

    for tag in args.tags:
        if tag in json:
            for role in json[tag]:
                print role



def actionAdd(args):
    json = readJson()

    for tag in args.tags:
        for role in args.role:
            if tag not in json:
                json[tag] = {}

            json[tag][role] = {
                'name': role,
                'added': int(time.time()),
                'priority': args.priority
            }

    saveJson(json)



def actionSummary(args):
    # list all roles in each possible tag
    for tag in PLAYBOOK_TAGS:
        roleList = buildRoleList([tag])
        if roleList:
            maxLength = len(max(roleList.keys(), key=len))

            print "Roles in " + tag + ":"
            for role in roleList:
                print ' - ' + role.ljust(maxLength, ' ') + '  [priority: ' + str(roleList[role]['priority']) + ']'
            print ''



def main(args):
    actions = {
        'list': actionList,
        'add': actionAdd,
        'summary': actionSummary,
        'playbook': actionPlaybook,
        'run': actionRun
    }

    func = actions.get(args.action, lambda: "nothing")
    return func(args)




if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    subparsers = parser.add_subparsers(
        title='subcommands',
        dest='action'
    )

    ###################################
    ## SUMMARY command
    summary = subparsers.add_parser('summary')

    ###################################
    ## RUN command
    run = subparsers.add_parser('run')
    run.add_argument(
        '--tag',
        dest='tags',
        choices=PLAYBOOK_TAGS,
        required=True,
        action='append',
        help='Ansible tag'
    )
    run.add_argument(
        '--playbook',
        dest='playbook',
        help='Ansible playbook'
    )
    run.add_argument(
        '--use-registry',
        dest='useRegistry',
        action='store_true',
        help='Use registred roles'
    )
    run.add_argument(
        '--role',
        dest='roles',
        action='append',
        help='Ansible role'
    )
    run.add_argument('args', nargs=argparse.REMAINDER)

    ###################################
    ## PLAYBOOK command
    playbook = subparsers.add_parser('playbook')
    playbook.add_argument(
        '--tag',
        dest='tags',
        choices=PLAYBOOK_TAGS,
        required=True,
        action='append',
        help='Ansible tag'
    )
    playbook.add_argument(
        '--use-registry',
        dest='useRegistry',
        action='store_true',
        help='Use registred roles'
    )
    playbook.add_argument(
        '--role',
        dest='roles',
        action='append',
        help='Ansible tag'
    )
    playbook.add_argument('args', nargs=argparse.REMAINDER)

    ###################################
    ## LIST command
    list = subparsers.add_parser('list')
    list.add_argument(
        '--tag',
        dest='tags',
        choices=PLAYBOOK_TAGS,
        required=True,
        action='append',
        help='Ansible tag'
    )
    list.add_argument('args', nargs=argparse.REMAINDER)

    ###################################
    ## ADD command
    add = subparsers.add_parser('add')
    add.add_argument(
        '--tag',
        dest='tags',
        choices=PLAYBOOK_TAGS,
        required=True,
        action='append',
        help='Ansible tag'
    )
    add.add_argument(
        '--priority',
        type=int,
        default=100,
        dest='priority',
        help='Priority for role [default 100, 1 is most important]'
    )
    add.add_argument('role', metavar='roles', nargs='+', help='Ansible roles')

    add.add_argument('args', nargs=argparse.REMAINDER)

    ## Execute
    args = parser.parse_args()
    main(args)
