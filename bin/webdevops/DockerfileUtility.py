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

DOCKERFILE_STATEMENT_FROM_RE = re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>[^\s:]+))?(?!.*\s+AS)', re.MULTILINE)
DOCKERFILE_STATEMENT_FROM_MULTISTAGE_RE = re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>[^\s:]+))?(\s+AS)', re.MULTILINE)

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
            for term in whitelist:
                if term in file:
                    tmp.append(file)
                    break
        file_list = tmp

    if blacklist:
        for term in blacklist:
            file_list = filter(lambda x: term not in x, file_list)

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

        image_is_duplicate = False

        # check if path is linked
        if os.path.islink(os.path.dirname(path)):
            linked_image_name_info = ([m.groupdict() for m in path_regex.finditer(os.path.realpath(path))])[0]

            linked_image_repository = (linked_image_name_info['repository'] if 'repository' in linked_image_name_info else '')
            linked_image_name = (linked_image_name_info['image'] if 'image' in linked_image_name_info else '')
            linked_image_tag = (linked_image_name_info['tag'] if 'tag' in linked_image_name_info else '')

            image_from = image_prefix + linked_image_repository + '/' + linked_image_name + ':' + linked_image_tag
            image_is_duplicate = True
        else:
            image_from = parse_dockerfile_from_statement(path)

        imageInfo = {
            'fullname': image_prefix + image_repository + '/' + image_name + ':' + image_tag,
            'name': image_prefix + image_repository + '/' + image_name,
            'tag': image_tag,
            'repository': image_prefix + image_repository,
            'imageName': image_name,
            'from': image_from,
            'duplicate': image_is_duplicate,
            'multiStageImages': parse_dockerfile_multistage_images(path)
        }
        return imageInfo

    ret = []
    for path in find_dockerfile_in_path_recursive(base_path):
        base_path = os.path.dirname(path)
        if os.path.isfile(path) and os.path.basename(path) == 'Dockerfile':
            dockerfile = {
                'path': path,
                'basePath': base_path,
                'abspath': os.path.abspath(path),
                'image': parse_docker_info_from_path(path),
            }
            ret.append(dockerfile)

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
            for term in whitelist:
                if term in dockerfile['image']['fullname']:
                    tmp.append(dockerfile)
                    break
        dockerfile_list = tmp

    if blacklist:
        for term in blacklist:
            dockerfile_list = filter(lambda x: term not in x['image']['fullname'], dockerfile_list)

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

def create_imagename_from_regex_result(data):
    """
    Create imagename from regex result (parsed Dockerfile)
    """
    ret = data['image']

    if data['tag']:
        ret += ':%s' % data['tag']

    ret = image_full_name(ret)

    return ret

def parse_dockerfile_from_statement(path):
    """
    Extract docker image name from FROM statement
    """
    with open(path, 'r') as fileInput:
        DockerfileContent = fileInput.read()
        data = ([m.groupdict() for m in DOCKERFILE_STATEMENT_FROM_RE.finditer(DockerfileContent)])[0]
        ret = create_imagename_from_regex_result(data)
    return ret

def parse_dockerfile_multistage_images(path):
    """
    Extract docker image name from FROM statement
    """
    ret = []

    with open(path, 'r') as fileInput:
        DockerfileContent = fileInput.read()

        for data in ([m.groupdict() for m in DOCKERFILE_STATEMENT_FROM_MULTISTAGE_RE.finditer(DockerfileContent)]):
            ret.append(create_imagename_from_regex_result(data))
    return ret

def image_full_name(image_name):
    """
    Return full name image if just short form (eg. alpine instead of alpine:latest)
    """
    if not re.search(':[^:]+$', image_name):
        image_name = '%s:latest' % image_name
    return image_name

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

def check_if_base_image_needs_pull(image, configuration):
    ret = False

    if configuration.get('docker.autoPull'):
        if configuration.get('docker.autoPullWhitelist') and configuration.get('docker.autoPullWhitelist').search(image):
            """
            Matched whitelist
            """
            ret = True
        else:
            """
            No whitelist, we need to pull every image
            """
            ret = True

        if configuration.get('docker.autoPullBlacklist') and configuration.get('docker.autoPullBlacklist').match(image):
            """
            Matched blacklist
            """
            ret = False
    return ret
