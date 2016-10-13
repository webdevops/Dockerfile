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


def findDockerfilesInPath(basePath, pathRegexp, imagePrefix, whitelist=False, blacklist=False):
    """
    Find all Dockerfiles in path (and even in symlinks and build dependencies)
    """

    def parseDockerInfoFromPath(path, pathRe, imagePrefix):
        imageNameInfo = ([m.groupdict() for m in pathRe.finditer(os.path.abspath(path))])[0]

        imageRepository = (imageNameInfo['repository'] if 'repository' in imageNameInfo else '')
        imageName = (imageNameInfo['image'] if 'image' in imageNameInfo else '')
        imageTag = (imageNameInfo['tag'] if 'tag' in imageNameInfo else '')

        if os.path.islink(os.path.dirname(path)):
            linkedImageNameInfo = ([m.groupdict() for m in pathRe.finditer(os.path.realpath(path))])[0]

            linkedImageRepository = (linkedImageNameInfo['repository'] if 'repository' in linkedImageNameInfo else '')
            linkedImageName = (linkedImageNameInfo['image'] if 'image' in linkedImageNameInfo else '')
            linkedImageTag = (linkedImageNameInfo['tag'] if 'tag' in linkedImageNameInfo else '')

            imageFrom = imagePrefix + linkedImageRepository + '/' + linkedImageName + ':' + linkedImageTag
        else:
            imageFrom = getDockerfileFrom(path)

        imageInfo = {
            'fullname': imagePrefix + imageRepository + '/' + imageName + ':' + imageTag,
            'name': imagePrefix + imageRepository + '/' + imageName,
            'tag': imageTag,
            'repository': imagePrefix + imageRepository,
            'from': imageFrom
        }
        return imageInfo

    dockerfileList = []
    pathRe = re.compile(pathRegexp)

    for path in recursiveDockerfileListInPath(basePath):
        if os.path.isfile(path) and os.path.basename(path) == 'Dockerfile':
            dockerfile = {
                'path': path,
                'abspath': os.path.abspath(path),
                'image': parseDockerInfoFromPath(path, pathRe, imagePrefix)
            }
            dockerfileList.append(dockerfile)

    if whitelist or blacklist:
        dockerfileList = filterDockerfiles(
            dockerfileList=dockerfileList,
            whitelist=whitelist,
            blacklist = blacklist
        )

    return dockerfileList


def filterDockerfiles(dockerfileList, whitelist=False, blacklist=False):
    """
    Filter Dockerfiles by white- and blacklist
    """

    if whitelist:
        tmp = []
        for dockerfile in dockerfileList:
            for whitelistTerm in whitelist:
                if whitelistTerm in dockerfile['image']['fullname']:
                    tmp.append(dockerfile)
        dockerfileList = tmp

    if blacklist:
        tmp = []
        for dockerfile in dockerfileList:
            for blacklistTerm in blacklist:
                if not blacklistTerm in dockerfile['image']['fullname']:
                    tmp.append(dockerfile)
        dockerfileList = tmp

    return dockerfileList


def recursiveDockerfileListInPath(basePath):
    """
    Find all Dockerfiles paths recursive in path
    """

    dockerfileList = []
    for root, subFolders, files in os.walk(basePath, followlinks=True):
        for file in files:
            if os.path.basename(file) == 'Dockerfile':
                dockerfileList.append(os.path.join(root, file))
    return dockerfileList


def getDockerfileFrom(path):
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


def generateImageNameLatest(imageName):
    """
    Prepare dockerfile list with dependency and also add "auto latest tag" images
    """
    if re.search(':[^:]+$', imageName):
        imageName = re.sub('(:[^:]+)$', ':latest', imageName)
    else:
        imageName = '%s:latest' % imageName
    return imageName
