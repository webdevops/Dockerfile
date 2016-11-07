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

from cleo import Output
from jinja2 import Environment, FileSystemLoader
from webdevops import BaseCommand
from webdevops import Dockerfile
from webdevops import DockerTestTaskLoader
from webdevops import DockerfileUtility
from webdevops import TestinfraDockerPlugin
from doit.doit_cmd import DoitMain
import pytest

import sys

class DockerTestCommand(BaseCommand):
    """
    Tests images

    docker:test
        {--dry-run               : show only which images will be build}
        {--t|threads=1           : threads}
        {--whitelist=?*          : image/tag whitelist }
        {--blacklist=?*          : image/tag blacklist }
    """

    def handle(self):
        configuration = self.get_configuration()

        configuration['threads'] = self.get_threads()

        configuration['whitelist'] = self.get_whitelist()
        configuration['blacklist'] = self.get_blacklist()

        configuration['dryRun'] = self.option('dry-run')

        if self.output.is_verbose():
            configuration['verbosity'] = 2

        if self.option('dry-run'):
            configuration['threads'] = 1
            configuration['verbosity'] = 2

        if configuration['threads'] > 1:
            doitOpts = []
            doitOpts.extend(['-n', str(configuration['threads']), '-P' 'thread'])
            exitcode = DoitMain(DockerTestTaskLoader(configuration)).run(doitOpts)
        else:
            testOpts = ['-x', self.configuration['testPath']]

            if self.output.is_verbose():
                testOpts.extend(['-v'])

            exitcode = pytest.main(testOpts, plugins = [TestinfraDockerPlugin.TestinfraDockerPlugin(configuration)])
        sys.exit(exitcode)
