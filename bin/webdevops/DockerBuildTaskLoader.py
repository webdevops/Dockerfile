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
import pytest
import testinfra
from webdevops import DockerfileUtility
from bunch import bunchify
from doit.task import dict_to_task
from doit.cmd_base import TaskLoader
from doit.doit_cmd import DoitMain


class DockerBuildTaskLoader(TaskLoader):

    configuration_default = {
        'basePath': False,

        'docker': {
            'imagePrefix': '',
            'autoLatestTag': False,
            'fromRegExp': re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>.+))?', re.MULTILINE),
            'pathRegex': False,
            'autoPull': False,
            'autoPullWhitelist': False,
            'autoPullBlacklist': False,
        },

        'dockerBuild': {
            'enabled': False,
            'noCache': False,
            'dryRun': False,
            'retry': 1
        },

        'dockerPush': {
            'enabled': False,
            'retry': 1
        },

        'dockerTest': {
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
        def dictmerge(original, update):
            """
            Recursively update a dict.
            Subdict's won't be overwritten but also updated.
            """
            for key, value in original.iteritems():
                if key not in update:
                    update[key] = value
                elif isinstance(value, dict):
                    dictmerge(value, update[key])
            return update

        """
        Build configuration as namespace object
        """
        self.configuration = bunchify(dictmerge(self.configuration_default, configuration))

        """
        Init docker client
        """
        self.dockerClient = docker.from_env(assert_hostname=False)

    def load_tasks(self, cmd, opt_values, pos_args):
        """
        DOIT task list generator
        """
        config = {'verbosity': self.configuration.verbosity}

        dockerfile_list = DockerfileUtility.find_dockerfiles_in_path(
            base_path=self.configuration.basePath,
            path_regex=self.configuration.docker.pathRegex,
            image_prefix=self.configuration.docker.imagePrefix,
            whitelist=self.configuration.whitelist,
            blacklist=self.configuration.blacklist,
        )
        dockerfile_list = self.process_dockerfile_list(dockerfile_list)

        # print json.dumps(dockerfile_list, sort_keys=True, indent = 4, separators = (',', ': '));sys.exit(0);

        taskList = []

        # Tasks: Docker build
        if self.configuration.dockerBuild.enabled:
            taskList.extend(self.generate_tasks_docker_build(dockerfile_list))

        # Tasks: Docker test
        if self.configuration.dockerTest.enabled:
            taskList.extend(self.generate_tasks_docker_test(dockerfile_list))

        # Tasks: Docker push
        if self.configuration.dockerPush.enabled:
            taskList.extend(self.generate_tasks_docker_push(dockerfile_list))

        return taskList, config

    def process_dockerfile_list(self, dockerfile_list):
        """
        Prepare dockerfile list with dependency and also add "auto latest tag" images
        """

        image_list = [x['image']['fullname'] for x in dockerfile_list if x['image']['fullname']]

        autoLatestTagImageList = []

        for dockerfile in dockerfile_list:
            # Calculate dependency
            dockerfile['dependency'] = False
            if dockerfile['image']['from'] and dockerfile['image']['from'] in image_list:
                dockerfile['dependency'] = dockerfile['image']['from']

            # Process auto latest tag
            if self.configuration.docker.autoLatestTag and dockerfile['image']['tag'] == self.configuration.docker.autoLatestTag:
                imageNameLatest = DockerfileUtility.generate_image_name_with_tag_latest(dockerfile['image']['fullname'])
                if imageNameLatest not in image_list:
                    autoLatestTagImage = copy.deepcopy(dockerfile)
                    autoLatestTagImage['image']['fullname'] = imageNameLatest
                    autoLatestTagImage['image']['tag'] = 'latest'
                    autoLatestTagImage['dependency'] = dockerfile['image']['fullname']
                    autoLatestTagImageList.append(autoLatestTagImage)

        # Add auto latest tag images to dockerfile list
        dockerfile_list.extend(autoLatestTagImageList)

        return dockerfile_list

    def generate_tasks_docker_build(self, dockerfileList):
        """
        Generate task list for docker build
        """
        taskList = []
        for dockerfile in dockerfileList:
            task = {
                'name': 'DockerBuild|%s' % dockerfile['image']['fullname'],
                'title': DockerBuildTaskLoader.task_title_build,
                'actions': [(DockerBuildTaskLoader.action_docker_build, [self.dockerClient, dockerfile, self.configuration])],
                'task_dep': []
            }

            if dockerfile['dependency']:
                task['task_dep'].append('DockerBuild|%s' % dockerfile['dependency']);

            taskList.append(dict_to_task(task))

        task = {
            'name': 'FinishChain|DockerBuild',
            'title': DockerBuildTaskLoader.task_title_finish,
            'actions': [(DockerBuildTaskLoader.action_chain_finish, ['docker build'])],
            'task_dep': [task.name for task in taskList]
        }
        taskList.append(dict_to_task(task))

        return taskList

    def generate_tasks_docker_test(self, dockerfile_list):
        """
        Generate task list for docker test
        """
        taskList = []

        for dockerfile in dockerfile_list:
            if dockerfile['test']:
                task = {
                    'name': 'DockerTest|%s' % dockerfile['image']['fullname'],
                    'title': DockerBuildTaskLoader.task_title_test,
                    'actions': [(DockerBuildTaskLoader.action_docker_test, [self.dockerClient, dockerfile, self.configuration])],
                    'task_dep': []
                }

                if self.configuration.dockerBuild.enabled:
                    task['task_dep'].append('FinishChain|DockerBuild')

                taskList.append(dict_to_task(task))

        task = {
            'name': 'FinishChain|DockerTest',
            'title': DockerBuildTaskLoader.task_title_finish,
            'actions': [(DockerBuildTaskLoader.action_chain_finish, ['docker test'])],
            'task_dep': [task.name for task in taskList]
        }
        taskList.append(dict_to_task(task))

        return taskList

    def generate_tasks_docker_push(self, dockerfile_list):
        """
        Generate task list for docker push
        """
        taskList = []


        for dockerfile in dockerfile_list:
            task = {
                'name': 'DockerPush|%s' % dockerfile['image']['fullname'],
                'title': DockerBuildTaskLoader.task_title_push,
                'actions': [(DockerBuildTaskLoader.action_docker_push, [self.dockerClient, dockerfile, self.configuration])],
                'task_dep': []
            }

            if self.configuration.dockerBuild.enabled:
                task['task_dep'].append('FinishChain|DockerBuild')

            taskList.append(dict_to_task(task))

        task = {
            'name': 'FinishChain|DockerPush',
            'title': DockerBuildTaskLoader.task_title_finish,
            'actions': [(DockerBuildTaskLoader.action_chain_finish, ['docker push'])],
            'task_dep': [task.name for task in taskList]
        }
        taskList.append(dict_to_task(task))

        return taskList

    @staticmethod
    def action_docker_build(docker_client, dockerfile, configuration, task):
        """
        Build one Dockerfile
        """

        pull_parent_image = DockerfileUtility.check_if_base_image_needs_pull(dockerfile, configuration)

        if configuration.dryRun:
            print '      from: %s (pull: %s)' % (dockerfile['image']['from'], ('yes' if pull_parent_image else 'no'))
            print '      path: %s' % dockerfile['path']
            print '       dep: %s' % (DockerBuildTaskLoader.human_task_name_list(task.task_dep) if task.task_dep else 'none')
            print ''
            return

        # Pull base image (FROM: xxx) first
        if pull_parent_image:
            print ' -> Pull base image %s ' % dockerfile['image']['from']

            pull_image_name = DockerfileUtility.image_basename(dockerfile['image']['from'])
            pull_image_tag = DockerfileUtility.extract_image_name_tag(dockerfile['image']['from'])

            pull_status = False
            for retry_count in range(0, configuration.dockerBuild.retry):
                response = docker_client.pull(
                    repository=pull_image_name,
                    tag=pull_image_tag,
                    stream=True,
                    decode=True
                )

                if DockerBuildTaskLoader.process_docker_client_response(response):
                    pull_status = True
                    break
                elif retry_count < (configuration.dockerBuild.retry - 1):
                    print '    failed, retrying... (try %s)' % (retry_count+1)
                else:
                    print '    failed, giving up'

        if not pull_status:
            return False

        ## Build image
        print ' -> Building image %s ' % dockerfile['image']['fullname']
        build_status = False
        for retry_count in range(0, configuration.dockerBuild.retry):
            response = docker_client.build(
                path=os.path.dirname(dockerfile['path']),
                tag=dockerfile['image']['fullname'],
                pull=False,
                nocache=configuration.dockerBuild.noCache,
                quiet=False,
                decode=True
            )

            if DockerBuildTaskLoader.process_docker_client_response(response):
                build_status = True
                break
            elif retry_count < (configuration.dockerBuild.retry-1):
                print '    failed, retrying... (try %s)' % (retry_count+1)
            else:
                print '    failed, giving up'

        return build_status

    @staticmethod
    def action_docker_test(docker_client, dockerfile, configuration, task):
        if dockerfile['test']:
            if configuration.dryRun:
                print '      from: %s' % dockerfile['image']['from']
                print '      path: %s' % dockerfile['test']['path']
                print ''
                return

        return True

    @staticmethod
    def action_docker_push(docker_client, dockerfile, configuration, task):
        """
        Push one Docker image to registry
        """
        if configuration.dryRun:
            print '       dep: %s' % (DockerBuildTaskLoader.human_task_name_list(task.task_dep) if task.task_dep else 'none')
            print ''
            return

        push_status = False
        for retry_count in range(0, configuration.dockerPush.retry):
            response = docker_client.push(
                dockerfile['image']['fullname'],
                stream=True,
                decode=True
            )
            if DockerBuildTaskLoader.process_docker_client_response(response):
                push_status = True
                break
            elif retry_count < (configuration.dockerBuild.retry - 1):
                print '    failed, retrying... (try %s)' % (retry_count+1)
            else:
                print '    failed, giving up'

        return push_status

    @staticmethod
    def process_docker_client_response(response):
        ret = True
        last_message = False

        def output_message(message, prevent_repeat=False):
            if 'last_message' not in output_message.__dict__:
                output_message.last_message = False

            # Prevent repeating messages
            if prevent_repeat:
                if output_message.last_message and output_message.last_message == message:
                    return
                output_message.last_message = message
            else:
                output_message.last_message = False

            sys.stdout.write(message.strip(' \t\n\r') + '\n')


        if not response:
            return False

        for line in response:
            # Keys
            #   - error
            #   - stream
            #   - status, progressDetail, id
            #   - progressDetail | aux [ tag, digest, size ]
            if 'error' in line:
                output_message(line['error'])
                ret = False
            if 'stream' in line:
                output_message(line['stream'], prevent_repeat=True)
            if 'status' in line:
                message = line['status']
                if 'id' in line:
                    message += ' ' + line['id']
                output_message(message)
        print ''
        return ret

    @staticmethod
    def human_task_name(name):
        """
        Translate internal task name to human readable name
        """
        return re.search('^.*\|(.*)', name).group(1)

    @staticmethod
    def human_task_name_list(list):
        """
        Translate list of internal task names to human readable names
        """
        ret = []
        for name in list:
            ret.append(DockerBuildTaskLoader.human_task_name(name))
        return ', '.join(ret)

    @staticmethod
    def action_chain_finish(title):
        """
        Action of finish chain
        """
        print ''

    @staticmethod
    def task_title_finish(task):
        """
        Finish task title function
        """
        return "Finished chain %s" % (DockerBuildTaskLoader.human_task_name(task.name))

    @staticmethod
    def task_title_build(task):
        """
        Build task title function
        """
        return "Docker build %s" % (DockerBuildTaskLoader.human_task_name(task.name))

    @staticmethod
    def task_title_test(task):
        """
        Build task title function
        """
        return "Docker test %s" % (DockerBuildTaskLoader.human_task_name(task.name))

    @staticmethod
    def task_title_push(task):
        """
        Push task title function
        """
        return "Docker push %s" % (DockerBuildTaskLoader.human_task_name(task.name))
0
