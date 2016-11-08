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
import re
from webdevops.BaseTaskLoader import BaseTaskLoader
from webdevops import TestinfraDockerPlugin
from doit.task import dict_to_task
import pytest

class DockerTestTaskLoader(BaseTaskLoader):

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

        'dockerTest': {
            'fileFilter': 'test_.*.py$'
        },

        'filter': {
            'whitelist': False,
            'blacklist': False,
        },

        'verbosity': 1,
        'threads': 1,
    }

    def get_testfile_list(self):
        ret = []
        filter_regexp = re.compile(self.configuration['dockerTest']['fileFilter'])

        for root, dirs, files in os.walk(self.configuration['testPath']):
            for file in files:
                if filter_regexp.search(file):
                    ret.append(os.path.relpath(os.path.join(root, file)))
        return ret

    def load_tasks(self, cmd, opt_values, pos_args):
        """
        DOIT task list generator
        """
        config = {'verbosity': self.configuration['verbosity']}

        taskList = []

        for testfile in self.get_testfile_list():
            task = {
                'name': 'DockerTest|%s' % testfile,
                'title': DockerTestTaskLoader.task_title_test,
                'actions': [(DockerTestTaskLoader.action_run_test, [testfile, self.configuration])],
                'task_dep': []
            }

            taskList.append(dict_to_task(task))

        return taskList, config

    @staticmethod
    def action_run_test(testfile, configuration, task):
        """
        Run test
        """

        if configuration['dryRun']:
            print '      testfile: %s' % (testfile)
            print ''
            return

        testOpts = []

        if configuration['verbosity'] > 1:
            testOpts.extend(['-v'])

        testOpts.append(testfile)

        exitcode = pytest.main(testOpts, plugins=[TestinfraDockerPlugin.TestinfraDockerPlugin(configuration)])

        if exitcode == 0:
            return True
        else:
            return False

    @staticmethod
    def task_title_test(task):
        """
        Build task title function
        """
        return "Run pytest %s" % (BaseTaskLoader.human_task_name(task.name))

