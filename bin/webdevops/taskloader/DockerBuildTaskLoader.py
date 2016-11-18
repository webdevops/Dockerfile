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
import sys
import re
import copy
import time
from .BaseTaskLoader import BaseTaskLoader
from .BaseDockerTaskLoader import BaseDockerTaskLoader
from webdevops import DockerfileUtility

class DockerBuildTaskLoader(BaseDockerTaskLoader):

    def generate_task_list(self, dockerfileList):
        """
        Generate task list for docker build
        """
        tasklist = []
        for dockerfile in dockerfileList:
            task = {
                'name': 'DockerBuild|%s' % dockerfile['image']['fullname'],
                'title': DockerBuildTaskLoader.task_title,
                'actions': [(BaseTaskLoader.task_runner, [DockerBuildTaskLoader.task_run, [self.docker_client, dockerfile, self.configuration]])],
                'task_dep': []
            }

            if dockerfile['dependency']:
                task['task_dep'].append('DockerBuild|%s' % dockerfile['dependency']);

            tasklist.append(task)

        # task = {
        #     'name': 'FinishChain|DockerBuild',
        #     'title': DockerBuildTaskLoader.task_title_finish,
        #     'actions': [(DockerBuildTaskLoader.action_chain_finish, ['docker build'])],
        #     'task_dep': [task.name for task in taskList]
        # }
        # tasklist.append(task)

        return tasklist

    @staticmethod
    def task_run(docker_client, dockerfile, configuration, task):
        """
        Build one Dockerfile
        """

        pull_parent_image = DockerfileUtility.check_if_base_image_needs_pull(dockerfile, configuration)

        if configuration['dryRun']:
            print '      from: %s (pull: %s)' % (dockerfile['image']['from'], ('yes' if pull_parent_image else 'no'))
            print '      path: %s' % dockerfile['path']
            print '       dep: %s' % (DockerBuildTaskLoader.human_task_name_list(task.task_dep) if task.task_dep else 'none')
            return True

        # Pull base image (FROM: xxx) first
        if pull_parent_image:
            print ' -> Pull base image %s ' % dockerfile['image']['from']

            pull_image_name = DockerfileUtility.image_basename(dockerfile['image']['from'])
            pull_image_tag = DockerfileUtility.extract_image_name_tag(dockerfile['image']['from'])

            pull_status = False
            for retry_count in range(0, configuration['retry']):
                pull_status = docker_client.pull_image(
                    name=pull_image_name,
                    tag=pull_image_tag,
                )

                if pull_status:
                    break
                elif retry_count < (configuration['retry'] - 1):
                    print '    failed, retrying... (try %s)' % (retry_count+1)
                else:
                    print '    failed, giving up'

            if not pull_status:
                return False

        ## Build image
        print ' -> Building image %s ' % dockerfile['image']['fullname']
        build_status = False
        for retry_count in range(0, configuration['retry']):
            build_status = docker_client.build_dockerfile(
                path=dockerfile['path'],
                name=dockerfile['image']['fullname'],
                nocache=configuration['dockerBuild']['noCache'],
            )

            if build_status:
                break
            elif retry_count < (configuration['retry']-1):
                print '    failed, retrying... (try %s)' % (retry_count+1)
            else:
                print '    failed, giving up'

        return build_status

    @staticmethod
    def task_title(task):
        """
        Build task title function
        """
        return "Docker build %s" % (BaseTaskLoader.human_task_name(task.name))
