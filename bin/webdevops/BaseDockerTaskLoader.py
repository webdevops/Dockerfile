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
import sys
import re
import copy
from webdevops.BaseTaskLoader import BaseTaskLoader
from webdevops import DockerfileUtility
from doit.task import dict_to_task


class BaseDockerTaskLoader(BaseTaskLoader):

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
            'noCache': False,
            'dryRun': False,
            'retry': 1
        },

        'dockerPush': {
            'retry': 1
        },

        'filter': {
            'whitelist': False,
            'blacklist': False,
        },

        'verbosity': 1,
        'threads': 1,
    }

    dockerClient = False

    def __init__(self, configuration):
        """
        Constrcutor
        """
        BaseTaskLoader.__init__(self, configuration)

        # Init docker client
        self.dockerClient = docker.from_env(assert_hostname=False)

    def load_tasks(self, cmd, opt_values, pos_args):
        """
        DOIT task list generator
        """
        config = {'verbosity': self.configuration['verbosity']}

        dockerfile_list = DockerfileUtility.find_dockerfiles_in_path(
            base_path=self.configuration['basePath'],
            path_regex=self.configuration['docker']['pathRegex'],
            image_prefix=self.configuration['docker']['imagePrefix'],
            whitelist=self.configuration['whitelist'],
            blacklist=self.configuration['blacklist'],
        )
        dockerfile_list = self.process_dockerfile_list(dockerfile_list)

        # print json.dumps(dockerfile_list, sort_keys=True, indent = 4, separators = (',', ': '));sys.exit(0);

        taskList = self.generate_task_list(dockerfile_list)

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
            if self.configuration['docker']['autoLatestTag'] and dockerfile['image']['tag'] == \
                self.configuration['docker'][
                    'autoLatestTag']:
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

    def generate_task_list(self, dockerfile_list):
        return []

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
