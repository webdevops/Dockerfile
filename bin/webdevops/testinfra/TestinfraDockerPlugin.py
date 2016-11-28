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

import pytest
import re
from webdevops import DockerfileUtility

class TestinfraDockerPlugin:
    docker_image_list = []
    configuration = False

    def __init__(self, configuration, docker_image=False):
        """
        Constructor
        """
        self.docker_image_list = []
        self.configuration = configuration
        self.init_docker_image_list(docker_image)

    def init_docker_image_list(self, docker_image=False):
        """
        Init and build list of available docker images
        """
        if docker_image:
            self.docker_image_list.append(docker_image)
        else:
            dockerfile_list = DockerfileUtility.find_dockerfiles_in_path(
                base_path=self.configuration.get('dockerPath'),
                path_regex=self.configuration.get('docker.pathRegex'),
                image_prefix=self.configuration.get('docker.imagePrefix'),
                whitelist=self.configuration.get('whitelist'),
                blacklist=self.configuration.get('blacklist'),
            )

            for image in dockerfile_list:
                self.docker_image_list.append(image['image']['fullname'])

    def get_image_list_by_regexp(self, filter_regexp):
        """
        Get image list by filtering via filter regexp
        """
        ret = []

        filter_regexp = re.compile(filter_regexp, re.IGNORECASE)

        for image_name in self.docker_image_list:
            if filter_regexp.search(image_name):
                ret.append(image_name)

        return ret

    def filter_list_by_term(self, list, term):
        """
        Filter list by using blacklist term
        """
        tmp = []
        for item in list:
            if not term in item:
                tmp.append(item)
        return tmp

    def pytest_generate_tests(self, metafunc):
        """
        Generate tests
        """
        if "TestinfraBackend" in metafunc.fixturenames:
            images = []

            # Lookup "docker_images" marker
            marker = getattr(metafunc.function, "docker_images", None)
            if marker is not None:
                for marker_image_name in marker.args:
                    images.extend(self.get_image_list_by_regexp(marker_image_name))

            # Lookup "docker_images.blacklist" marker
            marker = getattr(metafunc.function, "docker_images_blacklist", None)
            if marker is not None:
                for blacklist_term in marker.args:
                    images = self.filter_list_by_term(
                        list=images,
                        term=blacklist_term
                    )

            # Check for infinite loop
            marker = getattr(metafunc.function, "docker_loop", None)
            if marker is not None:
                images = ['{}#loop'.format(item) for item in images]

            # If the test has a destructive marker, we scope TestinfraBackend
            # at function level (i.e. executing for each test). If not we scope
            # at session level (i.e. all tests will share the same container)
            if getattr(metafunc.function, "destructive", None) is not None:
                scope = "function"
            else:
                scope = "session"

            metafunc.parametrize(
                "TestinfraBackend", images, indirect=True, scope=scope
            )
