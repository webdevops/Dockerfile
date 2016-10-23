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

DOCKERFILE_STATEMENT_FROM_RE = re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>.+))?', re.MULTILINE)

def find_file_in_path(dockerfile_path, filename="Dockerfile", whitelist=False, blacklist=False):
    """
    Search all file un dockerfile_path with filename ends with "filename"
    And match filter

    :param dockerfile_path: path where to search file
    :type dockerfile_path: str

    :param filename: pattern which the file must be validate
    :type filename: str

    :param whitelist: list of term must be match in path
    :type whitelist: list

    :param blacklist: list of term must not be match in path
    :type blacklist: list

    :return: list of path
    :rtype: list
    """
    file_list = []

    # build list of files
    for root, dirs, files in os.walk(dockerfile_path):
        for file in files:
            if file.endswith(filename):
                file_list.append(os.path.join(root, file))

    # filter by whitelist
    if whitelist:
        tmp = []
        for file in file_list:
            for whitelistTerm in whitelist:
                if whitelistTerm in file:
                    tmp.append(file)
                file_list = tmp

    # filter by blacklist
    if blacklist:
        tmp = []
        for file in file_list:
            for blacklistTerm in blacklist:
                if not blacklistTerm in file:
                    tmp.append(file)
                file_list = tmp

    return file_list

def find_dockerfiles_in_path(base_path, path_regex, image_prefix, whitelist=False, blacklist=False):
    """
    Find all Dockerfiles in path (and even in symlinks and build dependencies)
    """

    def parse_docker_info_from_path(path):
        image_name_info = ([m.groupdict() for m in path_regex.finditer(os.path.abspath(path))])[0]

        image_repository = (image_name_info['repository'] if 'repository' in image_name_info else '')
        image_name = (image_name_info['image'] if 'image' in image_name_info else '')
        image_tag = (image_name_info['tag'] if 'tag' in image_name_info else '')

        #
        #
        if os.path.islink(os.path.dirname(path)):
            linked_image_name_info = ([m.groupdict() for m in path_regex.finditer(os.path.realpath(path))])[0]

            linked_image_repository = (linked_image_name_info['repository'] if 'repository' in linked_image_name_info else '')
            linked_image_name = (linked_image_name_info['image'] if 'image' in linked_image_name_info else '')
            linked_image_tag = (linked_image_name_info['tag'] if 'tag' in linked_image_name_info else '')

            image_from = image_prefix + linked_image_repository + '/' + linked_image_name + ':' + linked_image_tag
        else:
            image_from = parse_dockerfile_from_statement(path)

        imageInfo = {
            'fullname': image_prefix + image_repository + '/' + image_name + ':' + image_tag,
            'name': image_prefix + image_repository + '/' + image_name,
            'tag': image_tag,
            'repository': image_prefix + image_repository,
            'from': image_from
        }
        return imageInfo

    def parse_docker_test_from_path(path):
        ret = False

        base_path = os.path.dirname(path)
        test_file_path = os.path.join(base_path, 'Dockerfile.test.py')

        if os.path.isfile(test_file_path):
            ret = {
                'path': test_file_path,
                'basePath': base_path,
            }
        return ret


    ret = []
    for path in find_dockerfile_in_path_recursive(base_path):
        base_path = os.path.dirname(path)
        if os.path.isfile(path) and os.path.basename(path) == 'Dockerfile':
            dockerfile = {
                'path': path,
                'basePath': base_path,
                'abspath': os.path.abspath(path),
                'image': parse_docker_info_from_path(path),
                'test': parse_docker_test_from_path(path),
            }
            ret.append(dockerfile)

    if whitelist or blacklist:
        ret = filter_dockerfile(
            dockerfile_list=ret,
            whitelist=whitelist,
            blacklist = blacklist
        )

    return ret


def filter_dockerfile(dockerfile_list, whitelist=False, blacklist=False):
    """
    Filter Dockerfiles by white- and blacklist
    """

    if whitelist:
        tmp = []
        for dockerfile in dockerfile_list:
            for whitelistTerm in whitelist:
                if whitelistTerm in dockerfile['image']['fullname']:
                    tmp.append(dockerfile)
            dockerfile_list = tmp

    if blacklist:
        tmp = []
        for dockerfile in dockerfile_list:
            for blacklistTerm in blacklist:
                if not blacklistTerm in dockerfile['image']['fullname']:
                    tmp.append(dockerfile)
                dockerfile_list = tmp

    return dockerfile_list


def find_dockerfile_in_path_recursive(basePath):
    """
    Find all Dockerfiles paths recursive in path
    """

    ret = []
    for root, subFolders, files in os.walk(basePath, followlinks=True):
        for file in files:
            if os.path.basename(file) == 'Dockerfile':
                ret.append(os.path.join(root, file))
    return ret


def parse_dockerfile_from_statement(path):
    """
    Extract docker image name from FROM statement
    """
    with open(path, 'r') as fileInput:
        DockerfileContent = fileInput.read()
        data = \
            ([m.groupdict() for m in DOCKERFILE_STATEMENT_FROM_RE.finditer(DockerfileContent)])[0]
        ret = data['image']

        if data['tag']:
            ret += ':%s' % data['tag']
    return ret


def generate_image_name_with_tag_latest(image_name):
    """
    Prepare dockerfile list with dependency and also add "auto latest tag" images
    """
    if re.search(':[^:]+$', image_name):
        ret = re.sub('(:[^:]+)$', ':latest', image_name)
    else:
        ret = '%s:latest' % image_name
    return ret

def image_basename(image_name):
    """
    Get image name without tag
    """
    if re.search(':[^:]+$', image_name):
        image_name = re.sub('(:[^:]+)$', '', image_name)
    return image_name

def extract_image_name_tag(image_name):
    """
    Get tag from image name
    """
    if re.search('^(.*):', image_name):
        ret = re.sub('^(.*):', '', image_name)
    else:
        ret = 'latest'
    return ret

def check_if_base_image_needs_pull(dockerfile, configuration):
    ret = False
    base_image = dockerfile['image']['from']

    if configuration.docker.autoPull:
        if configuration.docker.autoPullWhitelist and configuration.docker.autoPullWhitelist.search(base_image):
            """
            Matched whitelist
            """
            ret = True
        else:
            """
            No whitelist, we need to pull every image
            """
            ret = True

        if configuration.docker.autoPullBlacklist and configuration.docker.autoPullBlacklist.match(base_image):
            """
            Matched blacklist
            """
            ret = False
    return ret
