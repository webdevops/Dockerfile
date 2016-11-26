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
import os
import time
import datetime
import StringIO
import termcolor
from termcolor import colored

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

        self.out = ''.join([a.out for a in self.task.actions if a.out])
        self.err = ''.join([a.err for a in self.task.actions if a.err])
        self.error = error

        self.calculate_elapsed()

    def calculate_elapsed(self):
        """
        calculate elapsed time
        """
        if self._started_on is not None and self.elapsed is None:
            started = datetime.datetime.utcfromtimestamp(self._started_on)

            if self._finished_on is None:
                self._finished_on = time.time()

            self.started = str(started)
            self.elapsed = self._finished_on - self._started_on

    def to_dict(self):
        """
        convert result data to dictionary
        """

        self.calculate_elapsed()

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
        self.failures = []
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

    def add_failure(self, task, exception):
        """
        called when excution finishes with a failure
        """
        self.t_results[task.name].set_result('fail', exception.get_msg())

        if task.actions and (task.name[0] != '_'):
            duration = self.duration(self.t_results[task.name].elapsed)
            self.write(colored('.  %s FAILED (%s)\n' % (task.title(), duration), 'red'))
        self.failures.append({'task': task, 'exception': exception})

    def add_success(self, task):
        """
        called when excution finishes successfuly
        """
        self.t_results[task.name].set_result('success')

        if task.actions and (task.name[0] != '_'):
            duration = self.duration(self.t_results[task.name].elapsed)
            self.write(colored('.  %s finished (%s)\n' % (task.title(), duration), 'green'))

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
        
        self.writeln('')
        self.writeln('-> finished all tasks')
        self.writeln('')

        # sort task list by task name
        task_result_list = sorted(task_result_list, key=lambda task: task['name'])

        # show tasks if verbosity == 2
        if not self.show_out:
            for task in task_result_list:
                # Skip finish chain task (no content, just finish tasks)
                if 'FinishChain|' in task['name']:
                    continue

                self.task_stdout(
                    title=task['name'],
                    duration=task['elapsed'],
                    stdout=task['out'],
                    stderr=task['err'],
                    error=task['error']
                )

        # show failed tasks (at the end)
        for task in task_result_list:
            # Skip finish chain task (no content, just finish tasks)
            if 'FinishChain|' in task['name']:
                continue

            if task['result'] == 'fail':
                self.task_stdout(
                    title=task['name'],
                    duration=task['elapsed'],
                    stdout=task['out'],
                    stderr=task['err'],
                    error=task['error']
                )

        if self.errors:
            self.writeln("#" * 40 + "\n")
            self.writeln("Execution aborted.\n")
            self.writeln("\n".join(self.errors))
            self.writeln("\n")

    def task_stdout(self, title, duration=False, stdout=False, stderr=False, error=False, exception=False):
        """
        Show task output
        """

        text_duration = ''
        if duration:
            text_duration = ' (%s)' % self.duration(duration)

        title_full = 'Task %s%s:' % (title, text_duration)

        self.writeln(title_full)
        self.writeln('~' * len(title_full))

        if stdout:
            self.writeln()
            self.writeln('%s' % stdout)

        if stderr:
            self.writeln()
            self.writeln(colored('-- STDERR OUTPUT --', 'red'))
            self.write('%s' % stderr)

        if error:
            self.writeln()
            self.writeln(colored('-- ERROR OUTPUT --', 'red'))
            self.write('%s' % error)

        if exception:
            self.writeln()
            self.writeln(colored('-- EXCEPTION --', 'red'))
            self.write('%s' % exception.get_msg())

        self.writeln()
        self.writeln(':: end of output "%s"' % title)
        self.writeln()

    def duration(self, duration):
        """
        Calculate duration (seconds) to human readable time
        """
        return 'duration: %s' % str(datetime.timedelta(seconds=int(duration)))

    def writeln(self, text=''):
        """
        Output
        """
        self.outstream.write('%s\n' % text)

    def write(self, text):
        """
        Output
        """
        self.outstream.write(text)
