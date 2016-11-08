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

import re
from webdevops import DockerfileUtility
from doit.cmd_base import TaskLoader

class BaseTaskLoader(TaskLoader):
    configuration_default = {}

    configuration = False

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

        # Build configuration as namespace object
        self.configuration = dictmerge(self.configuration_default, configuration)


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
            ret.append(BaseTaskLoader.human_task_name(name))
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
        return "Finished chain %s" % (BaseTaskLoader.human_task_name(task.name))
