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

from cleo import Command, Output
from jinja2 import Environment, FileSystemLoader
from webdevops import Dockerfile
from webdevops import DockerBuildTaskLoader
from webdevops import DockerfileUtility
from doit.doit_cmd import DoitMain

import sys

class BuildImageCommand(Command):
    """
    Build images

    webdevops:build:image
        {--dry-run               : show only which images will be build}
        {--no-cache              : build without caching}
        {--t|threads=1           : threads}
        {--verbosity=1           : verbosity}
        {--build                 : Build Dockerfiles }
        {--push                  : Push Dockerfiles }
        {--whitelist=?*          : image/tag whitelist (TODO) }
        {--blacklist=?*          : image/tag blacklist (TODO) }
    """

    def handle(self):
        doitOpts = []

        configuration = {
            'basePath': './docker',
            'basePathWithRepository': False,

            'docker': {
                'imagePrefix'  : 'webdevops',
                'autoLatestTag': 'ubuntu-16.04',
                'pathRegexp'   : '/(?P<image>[^/]+)/(?P<tag>[^/]+)/Dockerfile$'
            },

            'dockerBuild': {
                'enabled'      : self.option('build'),
                'noCache'      : self.option('no-cache'),
                'dryRun'       : self.option('dry-run'),
            },

            'dockerPush': {
                'enabled'      : self.option('push'),
            },

            'filter': {
                'whitelist': False,
                'blacklist': False,
            },

            'verbosity': min(2, max(0, abs(int(self.option('verbosity'))))),
            'threads':   max(1, self.option('threads')),
        }

        if not configuration['dockerBuild']['enabled'] and not configuration['dockerPush']['enabled']:
                print ' [ERROR] Neither --build or --push was specified'
                sys.exit(1)

        if self.option('dry-run'):
            configuration['threads'] = 1
            configuration['verbosity'] = 2

        if configuration['threads'] > 1:
            doitOpts.extend(['-n', configuration['threads'], '-P' 'thread'])

        sys.exit(DoitMain(DockerBuildTaskLoader.DockerBuildTaskLoader(configuration)).run(doitOpts))



