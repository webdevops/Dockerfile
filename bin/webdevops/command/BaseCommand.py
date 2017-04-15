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

import os, sys, re, traceback
import time, datetime
import multiprocessing
from cleo import Command
from webdevops import Configuration
from ..doit.DoitReporter import DoitReporter

class BaseCommand(Command):
    configuration = False

    time_startup = False
    time_finish = False

    def __init__(self, configuration):
        """
        Constructor
        """
        Command.__init__(self)
        self.configuration = configuration

    def handle(self):
        """
        Main command method which will be called by Cleo
        """
        self.build_configuration()

        self.startup()

        try:
            exitcode = self.run_task(configuration=self.configuration)
        except KeyboardInterrupt as e:
            print ' !!! Execution aborted by user'
            exitcode = 1
        except SystemExit as e:
            print ' !!! Execution aborted by SystemExit'
            print ''
            traceback.print_exc(file=sys.stdout)
            exitcode = 1

        if exitcode == True or exitcode == 0 or exitcode == '' or exitcode is None:
            exitcode = 0
        elif exitcode == False:
            exitcode = 255

        self.shutdown(exitcode=exitcode)
        sys.exit(exitcode)

    def run_task(self, configuration):
        """
        Run task
        """
        return

    def startup(self):
        """
        Show startup message
        """
        self.time_startup = time.time()

        options = []

        if 'threads' in self.configuration:
            options.append('%s threads' % self.configuration.get('threads'))

        if 'retry' in self.configuration:
            options.append('%s retries' % self.configuration.get('retry', 1))

        if 'dryRun' in self.configuration and self.configuration.get('dryRun'):
            options.append('dry-run')
            DoitReporter.simulation_mode = True


        print 'Executing %s (%s)' % (self.name, ', '.join(options))
        print ''

        try:
            whitelist = self.get_whitelist()
            if whitelist:
                print 'WHITELIST active:'
                for item in whitelist:
                    print ' - %s' % item
                print ''
        except:
            pass

        try:
            blacklist = self.get_blacklist()
            if blacklist:
                print 'BLACKLIST active:'
                for item in blacklist:
                    print ' - %s' % item
                print ''
        except:
            pass

    def teardown(self,exitcode):
        pass

    def shutdown(self, exitcode=0):
        """
        Show shutdown message
        """

        self.time_finish = time.time()

        duration = self.time_finish - self.time_startup
        duration = str(datetime.timedelta(seconds=int(duration)))

        self.teardown(exitcode)

        print ''
        if exitcode == 0:
            print '> finished execution in %s successfully' % (duration)
        else:
            print '> finished execution in %s with errors (exitcode %s)' % (duration, exitcode)

    def build_configuration(self):
        """
        Get configuration
        """
        configuration = self.configuration

        # threads
        try:
            configuration.set('threads', self.get_threads())
        except (Exception):
            configuration.set('threads', 1)

        # whitelist
        try:
            configuration.set('whitelist', self.get_whitelist())
        except (Exception):
            pass

        # blacklist
        try:
            configuration.set('blacklist', self.get_blacklist())
        except (Exception):
            pass

        # dryrun
        try:
            configuration.set('dryRun', self.get_dry_run())
        except (Exception):
            pass

        # retry
        try:
            configuration.set('retry', self.get_retry())
        except (Exception):
            configuration.set('retry', 1)

        # verbosity
        if self.output.is_verbose():
            configuration.set('verbosity', 2)

        self.configuration = configuration

    def get_configuration(self):
        """
        Get configuration
        """
        return self.configuration

    def get_whitelist(self):
        """
        Get whitelist
        """

        # add whitelist from --whitelist
        ret = list(self.option('whitelist'))

        # add whitelist from argument list
        try:
            ret.extend(self.argument('docker images'))
        except (Exception):
            pass

        return ret

    def get_blacklist(self):
        """
        Get blacklist
        """
        ret = list(self.option('blacklist'))

        # static BLACKLIST file
        if os.path.isfile(self.configuration.get('blacklistFile')):
            lines = [line.rstrip('\n').lstrip('\n') for line in open(self.configuration.get('blacklistFile'))]
            lines = filter(bool, lines)

            if lines:
                ret.extend(lines)

        return ret

    def get_threads(self):
        """
        Get processing thread count
        """
        threads = os.getenv('THREADS', self.option('threads'))

        if threads == '0' or threads == '' or threads is None:
            # use configuration value
            threads = self.configuration.get('threads')

        match = re.match('auto(([-*+/])([0-9]+\.?[0-9]?))?', str(threads))
        if match is not None:
            ret = multiprocessing.cpu_count()

            if match.group(2) and match.group(3):
                math_sign = match.group(2)
                math_value = float(match.group(3))

                if math_sign == "*":
                    ret = int(ret * math_value)
                elif math_sign == "/":
                    ret = int(ret / math_value)
                elif math_sign == "+":
                    ret = int(ret + math_value)
                elif math_sign == "-":
                    ret = int(ret - math_value)
        else:
            ret = max(1, int(self.option('threads')))

        return int(ret)

    def get_dry_run(self):
        """
        Get if dry run is enabled
        """
        return bool(self.option('dry-run'))

    def get_retry(self):
        """
        Get number of retries
        """
        default = 1
        retry = os.getenv('RETRY', self.option('retry'))
        retry = max(0, int(retry))

        if retry > 0:
            # user value
            return retry
        elif 'retry' in self.configuration:
            # configuration value
            return self.configuration.get('retry')
        else:
            # defaults
            return default
