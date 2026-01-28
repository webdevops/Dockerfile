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

# Define standalone functions for better multiprocessing compatibility
def task_run_wrapper(dockerfile, configuration_dict, task):
    """
    Wrapper for docker push task that recreates objects in worker process
    """
    from webdevops.docker.DockerCliClient import DockerCliClient
    from webdevops.Configuration import dotdictify
    
    # Recreate objects in worker process
    docker_client = DockerCliClient()
    configuration = dotdictify(configuration_dict)
    
    return DockerPushTaskLoader.task_run(docker_client, dockerfile, configuration, task)

def task_runner_wrapper(func, args, task):
    """
    Wrapper for task runner
    """
    return BaseTaskLoader.task_runner(func, args, task)

class DockerPushTaskLoader(BaseDockerTaskLoader):
    cmd_options = ()

    def generate_task_list(self, dockerfile_list):
        """
        Generate task list for docker push
        """
        tasklist = []

        # Convert configuration to dict for serialization
        configuration_dict = self.configuration.to_dict()

        for dockerfile in dockerfile_list:
            task = {
                'name': 'DockerPush|%s' % dockerfile['image']['fullname'],
                'title': DockerPushTaskLoader.task_title,
                'actions': [(task_runner_wrapper, [task_run_wrapper, [dockerfile, configuration_dict]])],
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
            print(')      push: %s' % (dockerfile['image']['fullname']))
            return True

        push_status = False
        for retry_count in range(0, configuration.get('retry')):
            push_status = docker_client.push_image(
                name=dockerfile['image']['fullname'],
            )

            if push_status:
                break
            elif retry_count < (configuration.get('retry') - 1):
                print(')    failed, retrying... (try %s)' % (retry_count+1))
                time.sleep(randint(10, 30))
            else:
                print(')    failed, giving up')

        return push_status

    @staticmethod
    def task_title(task):
        """
        Push task title function
        """
        return "Docker push %s" % (BaseTaskLoader.human_task_name(task.name))
