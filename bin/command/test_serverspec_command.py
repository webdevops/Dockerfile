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

import os, glob
from cleo import Output
from webdevops import Dockerfile, DockerfileUtility
from webdevops.command import DoitCommand
from webdevops.taskloader import DockerTestServerspecTaskLoader

class TestServerspecCommand(DoitCommand):
    """
    Test docker images with Serverspec

    test:serverspec
        {docker images?*         : Docker images (whitelist)}
        {--dry-run               : show only which images will be build}
        {--t|threads=0           : threads}
        {--r|retry=0             : retry}
        {--whitelist=?*          : image/tag whitelist }
        {--blacklist=?*          : image/tag blacklist }
    """

    serverspec_path = False

    def run_task(self, configuration):
        self.serverspec_path = configuration.get('serverspecPath')

        self.cleanup_dockerfiles()

        return self.run_doit(
            task_loader=DockerTestServerspecTaskLoader(configuration),
            configuration=configuration
        )

    def cleanup_dockerfiles(self):
        """
        Cleanup old dockerfiles in test directory
        """
        for file in glob.glob(os.path.join(self.serverspec_path, 'Dockerfile.*.tmp')):
            os.remove(file)

    def teardown(self, exitcode):
        self.cleanup_dockerfiles()

