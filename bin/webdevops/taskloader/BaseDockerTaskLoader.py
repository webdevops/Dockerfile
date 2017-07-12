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

import os, sys, re, copy
from .BaseTaskLoader import BaseTaskLoader
from webdevops import DockerfileUtility
from doit.task import dict_to_task


class BaseDockerTaskLoader(BaseTaskLoader):

    docker_client = False

    def __init__(self, configuration):
        """
        Constrcutor
        """
        BaseTaskLoader.__init__(self, configuration)

        # Init docker client
        self.docker_client = configuration.get('dockerClient')

    def load_tasks(self, cmd, opt_values, pos_args):
        """
        DOIT task list generator
        """
        config = {'verbosity': self.configuration.get('verbosity')}

        dockerfile_list = DockerfileUtility.find_dockerfiles_in_path(
            base_path=self.configuration.get('dockerPath'),
            path_regex=self.configuration.get('docker.pathRegex'),
            image_prefix=self.configuration.get('docker.imagePrefix'),
            whitelist=self.configuration.get('whitelist'),
            blacklist=self.configuration.get('blacklist'),
        )
        dockerfile_list = self.process_dockerfile_list(dockerfile_list)

        #import json,sys;print json.dumps(dockerfile_list, sort_keys=True, indent = 4, separators = (',', ': '));sys.exit(0);

        tasklist = self.generate_task_list(dockerfile_list)

        if not tasklist or len(tasklist) == 0:
            raise Exception('No tasks found')

        tasklist = self.process_tasklist(tasklist)

        return tasklist, config

    def process_dockerfile_list(self, dockerfile_list):
        """
        Prepare dockerfile list with dependency and also add "auto latest tag" images
        """

        # Process auto latest tag
        autoLatestTagImageList = []
        image_list = [x['image']['fullname'] for x in dockerfile_list if x['image']['fullname']]
        for dockerfile in dockerfile_list:
            if self.configuration.get('docker.autoLatestTag') and dockerfile['image']['tag'] == self.configuration.get('docker.autoLatestTag'):
                imageNameLatest = DockerfileUtility.generate_image_name_with_tag_latest(dockerfile['image']['fullname'])
                if imageNameLatest not in image_list:
                    autoLatestTagImage = copy.deepcopy(dockerfile)
                    autoLatestTagImage['image']['fullname'] = imageNameLatest
                    autoLatestTagImage['image']['tag'] = 'latest'
                    autoLatestTagImage['image']['duplicate'] = True

                    if not 'dependency' in autoLatestTagImage:
                        autoLatestTagImage['dependency'] = []
                    autoLatestTagImage['dependency'].append(dockerfile['image']['fullname'])
                    autoLatestTagImageList.append(autoLatestTagImage)
        # Add auto latest tag images to dockerfile list
        dockerfile_list.extend(autoLatestTagImageList)

        # Calculate dependency
        image_list = [x['image']['fullname'] for x in dockerfile_list if x['image']['fullname']]
        for dockerfile in dockerfile_list:
            if not 'dependency' in dockerfile:
                dockerfile['dependency'] = []

            # add image from if it is a dependency
            if dockerfile['image']['from'] and dockerfile['image']['from'] in image_list:
                dockerfile['dependency'].append(dockerfile['image']['from'])

            # add multi stage image if it is a dependency
            for multiStageImage in dockerfile['image']['multiStageImages']:
                if multiStageImage in image_list:
                    dockerfile['dependency'].append(multiStageImage)

            # unique list
            unique_dep_list = []
            [unique_dep_list.append(item) for item in dockerfile['dependency'] if item not in unique_dep_list]
            dockerfile['dependency'] = unique_dep_list

        return dockerfile_list

    def generate_task_list(self, dockerfile_list):
        return []

