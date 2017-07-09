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

import os, re
from .BaseDockerTaskLoader import BaseDockerTaskLoader
from .BaseTaskLoader import BaseTaskLoader
from webdevops.testinfra import TestinfraDockerPlugin
from doit.task import dict_to_task
import pytest

class DockerTestTestinfraTaskLoader(BaseDockerTaskLoader):

    def generate_task_list(self, dockerfile_list):
        """
        Generate task list for docker push
        """
        tasklist = []

        for dockerfile in dockerfile_list:
            task = {
                'name': 'DockerTestTestinfra|%s' % dockerfile['image']['fullname'],
                'title': DockerTestTestinfraTaskLoader.task_title,
                'actions': [(BaseTaskLoader.task_runner, [DockerTestTestinfraTaskLoader.task_run, [dockerfile, self.configuration]])],
                'task_dep': []
            }

            for dep in dockerfile['dependency']:
                task['task_dep'].append('DockerTestTestinfra|%s' % dep)

            tasklist.append(task)

        # task = {
        #     'name': 'FinishChain|DockerTest',
        #     'title': DockerTestTestinfraTaskLoader.task_title_finish,
        #     'actions': [(DockerTestTestinfraTaskLoader.action_chain_finish, ['docker test'])],
        #     'task_dep': [task.name for task in taskList]
        # }
        # tasklist.append(task)

        return tasklist

    @staticmethod
    def task_run(dockerfile, configuration, task):
        """
        Run test
        """

        test_opts = []

        test_opts.extend(['-x', configuration.get('testinfraPath')])

        if configuration.get('verbosity') > 1:
            test_opts.extend(['-v'])

        if configuration.get('dryRun'):
            print '         image: %s' % (dockerfile['image']['fullname'])
            print '          args: %s' % (' '.join(test_opts))
            return True

        exitcode = pytest.main(test_opts, plugins=[TestinfraDockerPlugin(configuration=configuration, docker_image=dockerfile['image']['fullname'])])

        if exitcode == 0:
            return True
        else:
            return False

    @staticmethod
    def task_title(task):
        """
        Build task title function
        """
        return "Run pytest for %s" % (BaseTaskLoader.human_task_name(task.name))
