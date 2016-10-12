#!/usr/bin/env/python
# -*- coding: utf-8 -*-

import os
import docker
import json
import time
import sys
import re
import copy
from bunch import bunchify
from doit.task import dict_to_task
from doit.cmd_base import TaskLoader
from doit.doit_cmd import DoitMain


class DockerBuildTaskLoader(TaskLoader):

    defaultConfiguration = {
        'basePath': False,
        'basePathWithRepository': False,

        'docker': {
            'imagePrefix': '',
            'autoLatestTag': False,
            'fromRegExp': re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>.+))?', re.MULTILINE),
        },

        'dockerBuild': {
            'enabled': False,
            'noCache': False,
            'allowPull': False,  # only for non-depenency images?!
            'dryRun': False,
        },

        'dockerPush': {
            'enabled': False,
        },

        'filter': {
            'whitelist': False,
            'blacklist': False,
        },

        'verbosity': 1,
        'threads': 1,
    }

    configuration = False

    dockerClient = False

    def __init__(self, configuration):
        """
        Constrcutor
        """
        def mergeDict(original, update):
            """
            Recursively update a dict.
            Subdict's won't be overwritten but also updated.
            """
            for key, value in original.iteritems():
                if key not in update:
                    update[key] = value
                elif isinstance(value, dict):
                    mergeDict(value, update[key])
            return update

        """
        Build configuration as namespace object
        """
        self.configuration = bunchify(mergeDict(self.defaultConfiguration, configuration))

        """
        Init docker client
        """
        self.dockerClient = docker.from_env(assert_hostname=False)

    def load_tasks(self, cmd, opt_values, pos_args):
        """
        DOIT task list generator
        """
        config = {'verbosity': self.configuration.verbosity}

        if self.configuration.basePathWithRepository:
            dockerfileList = self.findDockerfileWithRepository(self.configuration.docker.imagePrefix, self.configuration.basePath)
        else:
            dockerfileList = self.findDockerfileByRepository(self.configuration.docker.imagePrefix, self.configuration.basePath)

        #print json.dumps(dockerfileList, sort_keys=True, indent = 4, separators = (',', ': '));sys.exit(0);

        taskList = []
        if self.configuration.dockerBuild.enabled:
            taskList.extend(self.generatDockerBuildTasks(dockerfileList))

        if self.configuration.dockerPush.enabled:
            taskList.extend(self.generatDockerPushTasks(dockerfileList))

        return taskList, config

    def generatDockerBuildTasks(self, dockerfileList):
        """
        Generate task list for docker build
        """
        taskList = []
        for dockerfile in dockerfileList:
            task = {
                'name': 'DockerBuild|%s' % dockerfile['image']['fullname'],
                'title': DockerBuildTaskLoader.taskTitleBuild,
                'actions': [(DockerBuildTaskLoader.actionDockerBuild, [self.dockerClient, dockerfile, self.configuration])],
                'task_dep': []
            }

            if dockerfile['dependency']:
                task['task_dep'].append('DockerBuild|%s' % dockerfile['dependency']);

            taskList.append(dict_to_task(task))

        task = {
            'name': 'FinishChain|DockerBuild',
            'title': DockerBuildTaskLoader.taskTitleFinish,
            'actions': [(DockerBuildTaskLoader.actionFinishChain, ['docker build'])],
            'task_dep': [task.name for task in taskList]
        }
        taskList.append(dict_to_task(task))

        return taskList

    def generatDockerPushTasks(self, dockerfileList):
        """
        Generate task list for docker push
        """
        taskList = []


        for dockerfile in dockerfileList:
            task = {
                'name': 'DockerPush|%s' % dockerfile['image']['fullname'],
                'title': DockerBuildTaskLoader.taskTitlePush,
                'actions': [(DockerBuildTaskLoader.actionDockerPush, [self.dockerClient, dockerfile, self.configuration])],
                'task_dep': []
            }

            if self.configuration.dockerBuild.enabled:
                task['task_dep'].append('FinishChain|DockerBuild')

            taskList.append(dict_to_task(task))

        task = {
            'name': 'FinishChain|DockerPush',
            'title': DockerBuildTaskLoader.taskTitleFinish,
            'actions': [(DockerBuildTaskLoader.actionFinishChain, ['docker push'])],
            'task_dep': [task.name for task in taskList]
        }
        taskList.append(dict_to_task(task))

        return taskList

    def getDockerfileFrom(self, path):
        """
        Extract image name from Dockerfile FROM statement or from symlink
        """
        basePath = os.path.dirname(path)

        if os.path.islink(basePath):
            """
            Dockerfile path is a symlink -> extract destination and
            """
            linkedPath = os.path.realpath(basePath)

            imageRepository = ''

            if self.configuration.basePathWithRepository:
                imageRepository = os.path.basename(os.path.dirname(os.path.dirname(linkedPath)))

            imageTag = os.path.basename(linkedPath);
            imageName = os.path.basename(os.path.dirname(linkedPath))
            ret = '%s%s/%s:%s' % (self.configuration.docker.imagePrefix, imageRepository, imageName, imageTag)
        else:
            """
            Extract docker image name from FROM statement
            """
            with open(path, 'r') as fileInput:
                DockerfileContent = fileInput.read()
                data = ([m.groupdict() for m in self.configuration.docker.fromRegExp.finditer(DockerfileContent)])[0]
                ret = data['image']

                if data['tag']:
                    ret += ':%s' % data['tag']
        return ret

    def findDockerfileWithRepository(self, dockerPrefix, basePath):
        """
        Find Dockerfiles in basePath/imageRepository/imageName/imageTag structrue
        """
        ret = []
        for imageRepository in os.listdir(basePath):
            if os.path.isdir(os.path.join(basePath, imageRepository)):
                ret = ret.extend(
                    self.findDockerfileByRepository( dockerPrefix + imageRepository, os.path.join(basePath, imageRepository))
                )
        return ret

    def findDockerfileByRepository(self, repository, basePath):
        """
        Find Dockerfiles in basePath/imageName/imageTag structrue
        """
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
                            'fullname': repository + '/' + imageName + ':' + imageTag,
                            'name': repository + '/' + imageName,
                            'tag':  imageTag,
                            'repository': repository,
                            'from': dockerImageFrom,
                        },
                        'dependency': dockerImageFrom
                    }

                    if self.configuration.docker.autoLatestTag and imageTag == self.configuration.docker.autoLatestTag:
                        autoLatestDefinition = copy.deepcopy(dockerDefinition)
                        autoLatestDefinition['image']['fullname'] = repository + '/' + imageName + ':latest'
                        autoLatestDefinition['image']['tag'] = 'latest'
                        autoLatestDefinition['dependency'] = repository + '/' + imageName + ':' + self.configuration.docker.autoLatestTag

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
    def actionDockerBuild(dockerClient, dockerfile, configuration, task):
        """
        Build one Dockerfile
        """
        if configuration.dockerBuild.dryRun:
            print '      from: %s' % dockerfile['image']['from']
            print '      path: %s' % dockerfile['path']
            print '       dep: %s' % (DockerBuildTaskLoader.humanTaskNameList(task.task_dep) if task.task_dep else 'none')
            print ''
            return

        allowPullFromHub = (True if dockerfile['dependency'] else False)

        response = dockerClient.build(
            path=dockerfile['path'],
            tag=dockerfile['image']['fullname'],
            pull=allowPullFromHub,
            nocache=configuration.dockerBuild.noCache,
            quiet=False,
            decode=True
        )

        for line in response:
            if 'stream' in line:
                sys.stdout.write(line['stream'])
        return True

    @staticmethod
    def actionDockerPush(dockerClient, dockerfile, configuration, task):
        """
        Push one Docker image to registry
        """
        if configuration.dockerBuild.dryRun:
            print '       dep: %s' % (DockerBuildTaskLoader.humanTaskNameList(task.task_dep) if task.task_dep else 'none')
            print ''
            return

        response = dockerClient.push(
            dockerfile['image']['fullname'],
            stream=True
        )

        for line in response:
            if 'stream' in line:
                sys.stdout.write(line['stream'])
        return True

    @staticmethod
    def humanTaskName(name):
        """
        Translate internal task name to human readable name
        """
        return re.search('^.*\|(.*)', name).group(1)

    @staticmethod
    def humanTaskNameList(nameList):
        """
        Translate list of internal task names to human readable names
        """
        ret = []
        for name in nameList:
            ret.append(DockerBuildTaskLoader.humanTaskName(name))
        return ', '.join(ret)

    @staticmethod
    def actionFinishChain(title):
        """
        Action of finish chain
        """
        print ''

    @staticmethod
    def taskTitleFinish(task):
        """
        Finish task title function
        """
        return "Finished chain %s" % (DockerBuildTaskLoader.humanTaskName(task.name))

    @staticmethod
    def taskTitleBuild(task):
        """
        Build task title function
        """
        return "Docker build %s" % (DockerBuildTaskLoader.humanTaskName(task.name))

    @staticmethod
    def taskTitlePush(task):
        """
        Push task title function
        """
        return "Docker push %s" % (DockerBuildTaskLoader.humanTaskName(task.name))
