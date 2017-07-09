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

import sys, re, time, StringIO, tempfile, json, base64, os
from webdevops import DockerfileUtility
from doit.cmd_base import TaskLoader
from doit.task import dict_to_task


class BaseTaskLoader(TaskLoader):
    configuration = False
    reporter = False
    TASK_COUNT = 0
    TASK_RESULTS = {}

    def __init__(self, configuration):
        """
        Constrcutor
        """
        # Build configuration as namespace object
        self.configuration = configuration

    def process_tasklist(self, tasklist):
        """
        Process task list and create task objects
        """
        ret = []
        for task in tasklist:
            ret.append(dict_to_task(task))

        print 'Starting execution of %s tasks...' % (len(ret))

        BaseTaskLoader.TASK_COUNT = len(ret)

        return ret

    @staticmethod
    def set_task_status(task, status, color):
        """
        Set task status
        """
        BaseTaskLoader.TASK_RESULTS[task.name] = {
            'task': task.name,
            'status': status,
            'color': color
        }
        BaseTaskLoader.task_write_statusfile(task, BaseTaskLoader.TASK_RESULTS[task.name])

    @staticmethod
    def get_task_status(task):
        ret = False
        if task in BaseTaskLoader.TASK_RESULTS:
            ret = BaseTaskLoader.TASK_RESULTS[task]
        return ret

    @staticmethod
    def human_task_name(name):
        """
        Translate internal task name to human readable name
        """
        res = re.search('^.*\|(.*)', name)

        if res:
            return re.search('^.*\|(.*)', name).group(1)
        else:
            return name


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
        return


    @staticmethod
    def task_title_finish(task):
        """
        Finish task title function
        """
        return "Finished chain %s" % (BaseTaskLoader.human_task_name(task.name))

    @staticmethod
    def task_runner(func, args, task):
        """
        Wrapper for task runner

        Will return the stdout if task fails as exception
        """
        backup = sys.stdout
        sys.stdout = StringIO.StringIO()
        status = func(task=task, *args)
        output = sys.stdout.getvalue().strip()
        sys.stdout.close()
        sys.stdout = backup

        if not status:
            raise Exception(output)
        else:
            print output

        return status



    @staticmethod
    def task_statusfile(task):
        return '%s/%s' % (tempfile.gettempdir(), base64.b64encode(task.name))

    @staticmethod
    def task_write_statusfile(task, data):
        filename = BaseTaskLoader.task_statusfile(task)
        with open(filename, "w") as f:
            f.write(json.dumps(data))
            f.flush()
            os.fsync(f.fileno())
            f.close()

    @staticmethod
    def task_get_statusfile(task, remove=True):
        ret = False
        filename = BaseTaskLoader.task_statusfile(task)

        if os.path.isfile(filename):
            with open(filename, "r") as f:
                try:
                    json_data = f.read()
                    ret = json.loads(json_data)
                except ValueError:
                    pass
                f.close()
            if remove:
                BaseTaskLoader.task_remove_statusfile(task)
        return ret

    @staticmethod
    def task_remove_statusfile(task):
        filename = '%s/%s' % (tempfile.gettempdir(), base64.b64encode(task.name))
        os.remove(filename)
