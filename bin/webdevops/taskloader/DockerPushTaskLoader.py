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

import os, sys, re, copy, time
from random import randint
from .BaseTaskLoader import BaseTaskLoader
from .BaseDockerTaskLoader import BaseDockerTaskLoader
from webdevops import DockerfileUtility

class DockerPushTaskLoader(BaseDockerTaskLoader):

    def generate_task_list(self, dockerfile_list):
        """
        Generate task list for docker push
        """
        tasklist = []

        for dockerfile in dockerfile_list:
            task = {
                'name': 'DockerPush|%s' % dockerfile['image']['fullname'],
                'title': DockerPushTaskLoader.task_title,
                'actions': [(BaseTaskLoader.task_runner, [DockerPushTaskLoader.task_run, [self.docker_client, dockerfile, self.configuration]])],
                'task_dep': []
            }

            for dep in dockerfile['dependency']:
                task['task_dep'].append('DockerPush|%s' % dep)

            tasklist.append(task)

        # task = {
        #     'name': 'FinishChain|DockerPush',
        #     'title': DockerPushTaskLoader.task_title_finish,
        #     'actions': [(DockerPushTaskLoader.action_chain_finish, ['docker push'])],
        #     'task_dep': [task.name for task in taskList]
        # }
        # tasklist.append(task)

        return tasklist

    @staticmethod
    def task_run(docker_client, dockerfile, configuration, task):
        """
        Push one Docker image to registry
        """
        if configuration.get('dryRun'):
            print '      push: %s' % (dockerfile['image']['fullname'])
            return True

        push_status = False
        for retry_count in range(0, configuration.get('retry')):
            push_status = docker_client.push_image(
                name=dockerfile['image']['fullname'],
            )

            if push_status:
                break
            elif retry_count < (configuration.get('retry') - 1):
                print '    failed, retrying... (try %s)' % (retry_count+1)
                time.sleep(randint(10, 30))
            else:
                print '    failed, giving up'

        return push_status

    @staticmethod
    def task_title(task):
        """
        Push task title function
        """
        return "Docker push %s" % (BaseTaskLoader.human_task_name(task.name))
0
