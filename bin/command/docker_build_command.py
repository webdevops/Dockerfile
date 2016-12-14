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
from webdevops import Dockerfile
from webdevops.command import DoitCommand
from webdevops.taskloader import DockerBuildTaskLoader

class DockerBuildCommand(DoitCommand):
    """
    Build images

    docker:build
        {docker images?*         : Docker images (whitelist)}
        {--dry-run               : show only which images will be build}
        {--no-cache              : build without caching}
        {--t|threads=0           : threads}
        {--r|retry=0             : retry}
        {--whitelist=?*          : image/tag whitelist }
        {--blacklist=?*          : image/tag blacklist }
    """

    def run_task(self, configuration):
        configuration.set('dockerBuild.noCache', self.option('no-cache'))

        return self.run_doit(
            task_loader=DockerBuildTaskLoader(configuration),
            configuration=configuration
        )



