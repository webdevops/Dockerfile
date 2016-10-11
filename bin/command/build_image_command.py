#! /usr/bin/env python

from cleo import Command, Output
from jinja2 import Environment, FileSystemLoader
from webdevops import Dockerfile
import os
import docker
import json
import time
import sys
import re
import copy
from doit.task import dict_to_task
from doit.cmd_base import TaskLoader
from doit.doit_cmd import DoitMain

DOCKER_LATEST_TAG = 'ubuntu-16.04'
DOCKER_REPOSITORY = 'webdevops'
DOCKER_FROM_REGEX = re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>.+))?', re.MULTILINE)

class BuildImageCommand(Command):
    """
    Build Dockerfile containers

    webdevops:build:image
        {--dry-run               : show only which images will be build}
        {--no-cache              : build without caching}
        {--pull                  : allow docker image pull}
        {--t|threads=1           : threads}
        {--verbosity=1           : verbosity}
        {--filter=?* : tags or images name }
    """

    def handle(self):
        doitArgs = []
        buildConfiguration = {
            'path':      './docker',
            'noCache':   self.option('no-cache'),
            'pull':      self.option('pull'),
            'dryRun':    self.option('dry-run'),
            'verbosity': min(2, max(0, abs(int(self.option('verbosity'))))),
            'threads':   max(1, self.option('threads')),
            'blacklist': max(1, self.option('threads')),
        }

        if buildConfiguration['dryRun']:
            buildConfiguration['threads'] = 1
            buildConfiguration['verbosity'] = 2

        if buildConfiguration['threads'] > 1:
            doitArgs.extend(['-n', buildConfiguration['threads']])

        sys.exit(DoitMain(DockerTaskLoader(buildConfiguration)).run(doitArgs))


class DockerTaskLoader(TaskLoader):
    buildConfiguration = {}

    def __init__(self, buildConfiguration):
        self.buildConfiguration = buildConfiguration

    def load_tasks(self, cmd, opt_values, pos_args):
        taskList = []
        config = {'verbosity': self.buildConfiguration['verbosity']}

        dockerClient = docker.from_env(assert_hostname=False)

        for dockerfile in self.findDockerfiles(self.buildConfiguration['path']):
            task = {
                'name': dockerfile['image']['fullname'],
                'title': DockerTaskLoader.taskTitle,
                'actions': [(DockerTaskLoader.dockerBuild, [dockerClient, dockerfile, self.buildConfiguration])],
                'targets': [dockerfile['image']['fullname']],
                'task_dep': []
            }

            if dockerfile['dependency']:
                task['task_dep'].append(dockerfile['dependency']);

            taskList.append(dict_to_task(task))
        return taskList, config

    def getDockerfileFrom(self, path):
        basePath = os.path.dirname(path)

        if os.path.islink(basePath):
            linkedPath = os.path.realpath(basePath)

            tagName = os.path.basename(linkedPath);
            imageName = os.path.basename(os.path.dirname(linkedPath))
            ret = '%s/%s:%s' % (DOCKER_REPOSITORY, imageName, tagName)
        else:
            with open(path, 'r') as fileInput:
                DockerfileContent = fileInput.read()
                data = ([m.groupdict() for m in DOCKER_FROM_REGEX.finditer(DockerfileContent)])[0]
                ret = '%s/%s' % (DOCKER_REPOSITORY, data['image'])

                if data['tag']:
                    ret += ':%s' % data['tag']
        return ret

    def findDockerfiles(self, basePath):
        ret = []

        for imageName in os.listdir(basePath):
            latestTagFound = False
            autoLatestDefinition = False

            for imageTag in os.listdir(os.path.join(basePath, imageName)):
                dockerImagePath = os.path.join(basePath, imageName, imageTag)
                dockerfilePath = os.path.join(dockerImagePath, 'Dockerfile')
                if os.path.isfile(dockerfilePath):
                    dockerImageFrom = self.getDockerfileFrom(dockerfilePath)

                    if imageTag == 'latest':
                        latestTagFound = True

                    dockerDefinition = {
                        'dockerfile': dockerfilePath,
                        'path': dockerImagePath,
                        'image': {
                            'fullname': DOCKER_REPOSITORY + '/' + imageName + ':' + imageTag,
                            'name': DOCKER_REPOSITORY + '/' + imageName,
                            'tag':  imageTag,
                            'repository': DOCKER_REPOSITORY,
                            'from': dockerImageFrom,
                        },
                        'dependency': dockerImageFrom
                    }

                    if imageTag == DOCKER_LATEST_TAG:
                        autoLatestDefinition = copy.deepcopy(dockerDefinition)
                        autoLatestDefinition['image']['fullname'] = DOCKER_REPOSITORY + '/' + imageName + ':latest'
                        autoLatestDefinition['image']['tag'] = 'latest'
                        autoLatestDefinition['dependency'] = DOCKER_REPOSITORY + '/' + imageName + ':' + DOCKER_LATEST_TAG

                    ret.append(dockerDefinition)

            if not latestTagFound and autoLatestDefinition:
                ret.append(autoLatestDefinition);

        ## remove not available dependencies
        imageList = [x['image']['fullname'] for x in ret if x['image']['fullname']]
        for i, row in enumerate(ret):
            if row['dependency'] and row['dependency'] not in imageList:
                row['dependency'] = False

        return ret

    @staticmethod
    def dockerBuild(dockerClient, task, buildConfiguration):
        if buildConfiguration['dryRun']:
            print '      from: %s' % task['image']['from']
            print '      path: %s' % task['path']
            print ''
            return

        response = dockerClient.build(
            path=task['path'],
            tag=task['image']['fullname'],
            pull=buildConfiguration['pull'],
            nocache=buildConfiguration['noCache'],
            quiet=False,
            decode=True
        )

        for line in response:
            if 'stream' in line:
                sys.stdout.write(line['stream'])
        return True

    @staticmethod
    def taskTitle(task):
        return "Building %s" % task.name
