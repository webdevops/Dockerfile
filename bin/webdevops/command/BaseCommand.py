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

import os, sys
import time, datetime
import multiprocessing
from cleo import Command
from webdevops import Configuration

class BaseCommand(Command):
    configuration = False

    time_startup = False
    time_finish = False

    def __init__(self, configuration):
        """
        Constructor
        """
        Command.__init__(self)
        self.configuration = Configuration.merge(configuration)

    def handle(self):
        self.startup()

        self.build_configuration()
        exitcode = self.run_task(configuration=self.configuration)

        if exitcode == False:
            exitcode = 255
        elif exitcode == '':
            exitcode = 0
        elif exitcode == None:
            exitcode = 0

        self.shutdown(exitcode=exitcode)

    def run_task(self, configuration):
        return

    def startup(self):
        self.time_startup = time.time()
        print 'Executing %s (threads: %s)' % (self.name, self.configuration['threads'])
        print ''

    def shutdown(self, exitcode=0):
        self.time_finish = time.time()

        duration = self.time_finish - self.time_startup
        duration = str(datetime.timedelta(seconds=int(duration)))

        print ''
        if exitcode == 0:
            print '> finished execution in %s' % (duration)
        else:
            print '> finished execution in %s with errors (exitcode %s)' % (duration, exitcode)
        sys.exit(exitcode)

    def build_configuration(self):
        """
        Get configuration
        """
        configuration = self.configuration

        # threads
        try:
            configuration['threads'] = self.get_threads()
        except (Exception):
            pass

        # whitelist
        try:
            configuration['whitelist'] = self.get_whitelist()
        except (Exception):
            pass

        # blacklist
        try:
            configuration['blacklist'] = self.get_blacklist()
        except (Exception):
            pass

        # dryrun
        try:
            configuration['dryRun'] = self.get_dry_run()
        except (Exception):
            pass

        # verbosity
        if self.output.is_verbose():
            configuration['verbosity'] = 2

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
        return self.option('whitelist')

    def get_blacklist(self):
        """
        Get blacklist
        """
        ret = self.option('blacklist')

        # static BLACKLIST file
        if os.path.isfile(self.configuration['blacklistFile']):
            with open(self.configuration['blacklistFile'], 'r') as ins:
                for line in ins:
                    ret.append(line)

        return ret

    def get_threads(self):
        """
        Get processing thread count
        """
        threads = os.getenv('THREADS', self.option('threads'))

        if threads == 'auto':
            ret = multiprocessing.cpu_count()
        else:
            ret = max(1, int(self.option('threads')))

        return int(ret)

    def get_dry_run(self):
        return self.option('dry-run')
