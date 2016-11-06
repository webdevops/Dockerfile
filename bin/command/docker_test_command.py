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
import testinfra
import pytest
import os
import re

import sys

class DockerTestCommand(Command):
    """
    Tests images

    docker:test
        {--dry-run               : show only which images will be build}
        {--t|threads=1           : threads}
        {--whitelist=?*          : image/tag whitelist }
        {--blacklist=?*          : image/tag blacklist }
    """

    configuration = False

    def __init__(self, configuration):
        Command.__init__(self)
        self.configuration = configuration

    def handle(self):
        testOpts = ['-x', self.configuration['basePath']]

        configuration = self.configuration

        configuration['threads'] = max(1, int(self.option('threads')))

        configuration['whitelist'] = self.option('whitelist')
        configuration['blacklist'] = self.option('blacklist')

        configuration['dryRun'] = self.option('dry-run')

        if self.output.is_verbose():
            configuration['verbosity'] = 2

        if self.option('dry-run'):
            configuration['threads'] = 1
            configuration['verbosity'] = 2

        if configuration['threads'] > 1:
            testOpts.extend(['-n', configuration['threads']])

        if 'verbosity' in configuration and configuration['verbosity'] > 1:
            testOpts.extend(['-v'])

        class DockerPlugin:
            docker_image_list = []
            configuration = False

            def __init__(self, configuration):
                self.configuration = configuration
                self.init_docker_image_list()

            def init_docker_image_list(self):
                dockerfile_list = DockerfileUtility.find_dockerfiles_in_path(
                    base_path=self.configuration['basePath'],
                    path_regex=self.configuration['docker']['pathRegex'],
                    image_prefix=self.configuration['docker']['imagePrefix'],
                    whitelist=self.configuration['whitelist'],
                    blacklist=self.configuration['blacklist'],
                )

                for image in dockerfile_list:
                    self.docker_image_list.append(image['image']['fullname'])

            def get_image_list_by_regexp(self, filter_regexp):
                ret = []

                filter_regexp = re.compile(filter_regexp)

                for image_name in self.docker_image_list:
                    if filter_regexp.match(image_name):
                        ret.append(image_name)

                return ret

            def pytest_sessionstart(self):
                print("\n*** test run reporting starting")

            def pytest_sessionfinish(self):
                print("\n*** test run reporting finishing")

            def pytest_generate_tests(self, metafunc):
                if "TestinfraBackend" in metafunc.fixturenames:
                    images = []

                    # Lookup "docker_images" marker
                    marker = getattr(metafunc.function, "docker_images", None)
                    if marker is not None:
                        for marker_image_name in marker.args:
                            images.extend(self.get_image_list_by_regexp(marker_image_name))
                    print images

                    # If the test has a destructive marker, we scope TestinfraBackend
                    # at function level (i.e. executing for each test). If not we scope
                    # at session level (i.e. all tests will share the same container)
                    if getattr(metafunc.function, "destructive", None) is not None:
                        scope = "function"
                    else:
                        scope = "session"

                    metafunc.parametrize(
                        "TestinfraBackend", images, indirect=True, scope=scope)

        sys.exit(pytest.main(testOpts, plugins = [DockerPlugin(configuration)]))

