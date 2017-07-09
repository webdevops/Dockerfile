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

import os, sys, time, datetime, StringIO
import termcolor
from termcolor import colored
from ..taskloader.BaseTaskLoader import BaseTaskLoader

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

    skip_detection = True
    simulation_mode = False

    desc = 'output after finish'

    show_out = False
    show_err = False
    task_finished = 0
    task_total = 0

    COLOR_SCHEMA = {
        'skipped':   'yellow',
        'simulated': 'blue',
        'success':   'green',
        'success2':  'cyan',
        'failed':    'red',
    }

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

    def collect_task_result(self, task):
        task_status = BaseTaskLoader.task_get_statusfile(task)
        if task_status:
            BaseTaskLoader.TASK_RESULTS[task.name] = task_status

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
        self.collect_task_result(task)
        self.t_results[task.name].set_result('fail', exception.get_msg())

        self.task_finished += 1

        if task.actions and (task.name[0] != '_'):
            duration = self.duration(self.t_results[task.name].elapsed)
            progress = self.calc_progress()
            self.writeln(colored('.  %s FAILED (%s, #%s)' % (BaseTaskLoader.human_task_name(task.title()), duration, progress), DoitReporter.COLOR_SCHEMA['failed']))
        self.failures.append({'task': task, 'exception': exception})

    def add_success(self, task):
        """
        called when excution finishes successfuly
        """
        self.collect_task_result(task)
        self.t_results[task.name].set_result('success')

        self.task_finished += 1

        if task.actions and (task.name[0] != '_'):
            durationSeconds = self.t_results[task.name].elapsed
            duration = self.duration(durationSeconds)
            progress = self.calc_progress()

            if DoitReporter.simulation_mode:
                output_status = 'simulated'
                output_color = DoitReporter.COLOR_SCHEMA['simulated']
            else:
                if DoitReporter.skip_detection and durationSeconds < 1:
                    output_status = 'SKIPPED'
                    output_color = DoitReporter.COLOR_SCHEMA['skipped']
                else:
                    output_status = 'finished'
                    output_color = DoitReporter.COLOR_SCHEMA['success']

            # custom status
            task_status = BaseTaskLoader.get_task_status(task.name)
            if task_status:
                if task_status['color'] and task_status['color'] in DoitReporter.COLOR_SCHEMA:
                    output_color = DoitReporter.COLOR_SCHEMA[task_status['color']]
                if task_status['status']:
                    output_status = task_status['status']

            self.writeln(
                colored('.  %s %s (%s, %s)' % (BaseTaskLoader.human_task_name(task.title()), output_status, duration, progress), output_color)
            )

    def skip_uptodate(self, task):
        """
        skipped up-to-date task
        """
        self.t_results[task.name].set_result('up-to-date')

    def skip_ignore(self, task):
        """
        skipped ignored task
        """
        self.collect_task_result(task)
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

        task_result_list = [tr.to_dict() for tr in self.t_results.values()]

        self.writeln('')
        self.writeln('-> finished %s tasks' % (len(task_result_list)))
        self.writeln('')

        # sort task list by task name
        task_result_list = sorted(task_result_list, key=lambda task: task['name'])

        # show tasks if verbosity == 2
        if not self.show_out:
            for task in task_result_list:
                # Skip finish chain task (no content, just finish tasks)
                if 'FinishChain|' in task['name']:
                    continue

                if task['result'] != 'fail':
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

        title_full = 'Task %s%s:' % (BaseTaskLoader.human_task_name(title), text_duration)

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
        self.writeln(':: end of output "%s"' % (BaseTaskLoader.human_task_name(title)))
        self.writeln()

    def duration(self, duration):
        """
        Calculate duration (seconds) to human readable time
        """
        return 'duration: %s' % str(datetime.timedelta(seconds=int(duration)))

    def calc_progress(self):
        percentage = 100 * float(self.task_finished)/float(BaseTaskLoader.TASK_COUNT)
        return 'task %s/%s, progress %d%%' % (self.task_finished, BaseTaskLoader.TASK_COUNT, percentage)

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
