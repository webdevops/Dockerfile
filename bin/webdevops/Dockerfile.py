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

import os
import re


def finder(dockerfile_path, filename="Dockerfile", filter=[]):
    """
    Search all file un dockerfile_path with filename ends with "filename"
    And match filter

    :param dockerfile_path: path where to search file
    :type dockerfile_path: str

    :param filename: pattern which the file must be validate
    :type filename: str

    :param filter: list of term must be match in path
    :type filter: list

    :return: list of path
    :rtype: list
    """
    dockerfile_stack = []
    filter_regex = re.compile(ur'.*(%s).*' % "|".join(filter), re.IGNORECASE)
    # pprint(filter_regex.pattern)
    for root, dirs, files in os.walk(dockerfile_path):
        for file in files:
            if file.endswith(filename):
                if filter_regex.match(root):
                    dockerfile_stack.append(os.path.join(root, file))
    return dockerfile_stack


def find_by_image(dockerfile_path, filename="Dockerfile", image=[]):
    """
    Similar that finder but adds a constraint of search on the name of the image docker

    :param dockerfile_path: path where to search file
    :type dockerfile_path: str

    :param filename: pattern which the file must be validate
    :type filename: str

    :param image: list of term must be match in path
    :type image: list

    :return: list of path
    :rtype: list
    """
    updated_filter = []
    for i, v in enumerate(image):
        updated_filter.append("/docker/(%s)/" % v)
    return finder(dockerfile_path, filename, updated_filter)


def find_by_image_and_tag(dockerfile_path, image, tag):
    """

    :param dockerfile_path:
    :type dockerfile_path: str

    :param image:
    :type image: str

    :param tag:
    :type tag: str

    :return:
    :rtype: list
    """
    if "*" == tag:
        filter = ["/docker/%s/" % image]
    else:
        filter = ["/docker/%s/%s" % (image, tag.replace('*', '.+'))]
    return finder(dockerfile_path, "Dockerfile", filter)


def find_by_tags(dockerfile_path, filename="Dockerfile", tags=[]):
    """
    Similar that finder but adds a constraint of search on the tag of the image docker

    :param dockerfile_path: path where to search file
    :type dockerfile_path: str

    :param filename: pattern which the file must be validate
    :type filename: str

    :param tags: list of tags must be match in path
    :type tags: list

    :return: list of path
    :rtype: list
    """
    updated_filter = []
    for i, v in enumerate(tags):
        updated_filter.append("/docker/[^/]+/%s" % v)
    return finder(dockerfile_path, filename, updated_filter)
