#!/usr/bin/env/python
# -*- coding: utf-8 -*-
#
# (c) 2016 WebDevOps.io
#
# This file is part of Dockerfile Repository.
#
# Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
# documentation files (the "Software"), to deal in the Software without restriction, including without limitation
# the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
# to permit persons to whom the Software is furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all copies or substantial portions
# of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
# THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
# OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
# OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

import os
import docker
import json
import time
import sys
import re
import copy
from webdevops import DockerfileUtility
from bunch import bunchify
from doit.task import dict_to_task
from doit.cmd_base import TaskLoader
from doit.doit_cmd import DoitMain


class DockerBuildTaskLoader(TaskLoader):

    defaultConfiguration = {
        'basePath': False,

        'docker': {
            'imagePrefix': '',
            'autoLatestTag': False,
            'fromRegExp': re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>.+))?', re.MULTILINE),
            'pathRegexp': False,
        },

        'dockerBuild': {
            'enabled': False,
            'noCache': False,
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

        dockerfileList = DockerfileUtility.findDockerfilesInPath(
            basePath=self.configuration.basePath,
            pathRegexp=self.configuration.docker.pathRegexp,
            imagePrefix=self.configuration.docker.imagePrefix,
            whitelist=self.configuration.whitelist,
            blacklist=self.configuration.blacklist,
        )
        dockerfileList = self.prepareDockerfileList(dockerfileList)

        # print json.dumps(dockerfileList, sort_keys=True, indent = 4, separators = (',', ': '));sys.exit(0);

        taskList = []
        if self.configuration.dockerBuild.enabled:
            taskList.extend(self.generatDockerBuildTasks(dockerfileList))

        if self.configuration.dockerPush.enabled:
            taskList.extend(self.generatDockerPushTasks(dockerfileList))

        return taskList, config

    def prepareDockerfileList(self, dockerfileList):
        """
        Prepare dockerfile list with dependency and also add "auto latest tag" images
        """

        def generateImageNameLatest(imageName):
            """
            Generate image name with latest tag
            """
            if re.search(':[^:]+$', imageName):
                imageName = re.sub('(:[^:]+)$', ':latest', imageName)
            else:
                imageName = '%s:latest' % imageName
            return imageName

        imageList = [x['image']['fullname'] for x in dockerfileList if x['image']['fullname']]

        autoLatestTagImageList = []

        for dockerfile in dockerfileList:
            """
            Calculate dependency
            """
            dockerfile['dependency'] = False
            if dockerfile['image']['from'] and dockerfile['image']['from'] in imageList:
                dockerfile['dependency'] = dockerfile['image']['from']

            """
            Process auto latest tag
            """
            if self.configuration.docker.autoLatestTag and dockerfile['image']['tag'] == self.configuration.docker.autoLatestTag:
                imageNameLatest = generateImageNameLatest(dockerfile['image']['fullname'])
                if imageNameLatest not in imageList:
                    autoLatestTagImage = copy.deepcopy(dockerfile)
                    autoLatestTagImage['image']['fullname'] = imageNameLatest
                    autoLatestTagImage['image']['tag'] = 'latest'
                    autoLatestTagImage['dependency'] = dockerfile['image']['fullname']
                    autoLatestTagImageList.append(autoLatestTagImage)

        """
        Add auto latest tag images to dockerfile list
        """
        dockerfileList.extend(autoLatestTagImageList)

        return dockerfileList




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
            stream=True,
            decode=True
        )

        for line in response:
            """
            Keys
              - status, progressDetail, id
              - progressDetail | aux [ tag, digest, size ]
            """
            if 'status' in line:
                message = line['status']
                if 'id' in line:
                    message += ' ' + line['id']
                sys.stdout.write(message + '\n')
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
0
