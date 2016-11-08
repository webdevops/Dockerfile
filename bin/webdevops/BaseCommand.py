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

from cleo import Command
import os
import multiprocessing

class BaseCommand(Command):
    configuration = False

    def __init__(self, configuration):
        """
        Constructor
        """
        Command.__init__(self)
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
