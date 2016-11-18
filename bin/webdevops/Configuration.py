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

import re
from webdevops.doit.DoitReporter import DoitReporter
from webdevops.docker.DockerCliClient import DockerCliClient

default = {
    'basePath': False,
    'templatePath': False,
    'provisionPath': False,
    'imagePath': False,
    'confPath': False,
    'baselayoutPath': False,
    'testPath': False,

    'dockerClient': DockerCliClient(),

    'blacklistFile': False,

    'doitConfig': {
        'GLOBAL': {
            'reporter': DoitReporter,
        }
    },

    'docker': {
        'imagePrefix': '',
        'autoLatestTag': False,
        'fromRegExp': re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>.+))?', re.MULTILINE),
        'pathRegex': False,
        'autoPull': False,
        'autoPullWhitelist': False,
        'autoPullBlacklist': False,
    },

    'dockerTest': {
        'fileFilter': 'test_.*.py$'
    },

    'dockerBuild': {
        'noCache': False,
    },

    'dockerPush': {
    },

    'filter': {
        'whitelist': False,
        'blacklist': False,
    },

    'verbosity': 0,
    'threads': 1,
    'retry': 5,
    'dryRun': False,
}

def merge(configuration):
    def dictmerge(original, update):
        """
        Recursively update a dict.
        Subdict's won't be overwritten but also updated.
        """
        for key, value in original.iteritems():
            if key not in update:
                update[key] = value
            elif isinstance(value, dict):
                dictmerge(value, update[key])
        return update

    return dictmerge(default, configuration)

