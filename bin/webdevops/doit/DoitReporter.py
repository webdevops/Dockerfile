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

import sys
import time
import datetime
import StringIO

class TaskResult(object):
    """
    result object used by DoitReporter
    """
    # FIXME what about returned value from python-actions ?
    def __init__(self, task):
        self.task = task
        self.result = None # fail, success, up-to-date, ignore
        self.out = None # stdout from task
        self.err = None # stderr from task
        self.error = None # error from doit (exception traceback)
        self.started = None # datetime when task execution started
        self.elapsed = None # time (in secs) taken to execute task
        self._started_on = None # timestamp
        self._finished_on = None # timestamp

    def start(self):
        """
        called when task starts its execution
        """
        self._started_on = time.time()

    def set_result(self, result, error=None):
        """
        called when task finishes its execution
        """
        self._finished_on = time.time()
        self.result = result
        line_sep = ""
        self.out = line_sep.join([a.out for a in self.task.actions if a.out])
        self.err = line_sep.join([a.err for a in self.task.actions if a.err])
        self.error = error

    def to_dict(self):
        """
        convert result data to dictionary
        """
        if self._started_on is not None:
            started = datetime.datetime.utcfromtimestamp(self._started_on)
            self.started = str(started)
            self.elapsed = self._finished_on - self._started_on

        ret = {
            'name': self.task.name,
            'result': self.result,
            'out': self.out,
            'err': self.err,
            'error': self.error,
            'started': self.started,
            'elapsed': self.elapsed
        }
        return ret


class DoitReporter(object):
    """
    output results after finish
    """

    desc = 'output after finish'

    show_out = False
    show_err = False

    def __init__(self, outstream, options=None): #pylint: disable=W0613
        # result is sent to stdout when doit finishes running
        self.t_results = {}
        # when using this reporter output can not contain any other output
        # than the data. so anything that is sent to stdout/err needs to
        # be captured.
        self._old_out = sys.stdout
        sys.stdout = StringIO.StringIO()
        self._old_err = sys.stderr
        sys.stderr = StringIO.StringIO()
        self.outstream = outstream
        # runtime and cleanup errors
        self.errors = []

        self.show_out = options.get('show_out', True)
        self.show_err = options.get('show_err', True)

    def get_status(self, task):
        """
        called when task is selected (check if up-to-date)
        """
        self.t_results[task.name] = TaskResult(task)

    def execute_task(self, task):
        """
        called when excution starts
        """
        self.t_results[task.name].start()

        if task.actions and (task.name[0] != '_'):
            self.write('.  %s started\n' % task.title())

    def add_failure(self, task, exception):
        """
        called when excution finishes with a failure
        """
        self.t_results[task.name].set_result('fail', exception.get_msg())

        if task.actions and (task.name[0] != '_'):
            self.write('.  %s FAILED\n' % task.title())

    def add_success(self, task):
        """
        called when excution finishes successfuly
        """
        self.t_results[task.name].set_result('success')

        if task.actions and (task.name[0] != '_'):
            self.write('.  %s finished\n' % task.title())

    def skip_uptodate(self, task):
        """
        skipped up-to-date task
        """
        self.t_results[task.name].set_result('up-to-date')

    def skip_ignore(self, task):
        """
        skipped ignored task
        """
        self.t_results[task.name].set_result('ignore')

    def cleanup_error(self, exception):
        """
        error during cleanup
        """
        self.errors.append(exception.get_msg())

    def runtime_error(self, msg):
        """
        error from doit (not from a task execution)
        """
        self.errors.append(msg)

    def teardown_task(self, task):
        """
        called when starts the execution of teardown action
        """
        pass

    def complete_run(self):
        """
        called when finshed running all tasks
        """
        # restore stdout
        log_out = sys.stdout.getvalue()
        sys.stdout = self._old_out
        log_err = sys.stderr.getvalue()
        sys.stderr = self._old_err

        # add errors together with stderr output
        if self.errors:
            log_err += "\n".join(self.errors)

        task_result_list = [
            tr.to_dict() for tr in self.t_results.values()]

        print ''
        print '-> finished all tasks'
        print ''

        for task in task_result_list:
            title = 'Task %s (duration: %s):' % (task['name'], self.duration(task['elapsed']))

            if self.show_out:
                print title
                print '=' * len(title)
                print '%s' % task['out']
                print ''
                print ''
            else:
                if self.show_err and task['err']:
                    print title
                    print '=' * len(title)
                    print '%s' % task['err']
                    print ''
                    print ''

    def duration(self, seconds):
        """
        Humanized duration
        """
        return str(datetime.timedelta(seconds=int(seconds)))

    def write(self, text):
        """
        Output
        """
        self.outstream.write(text)
