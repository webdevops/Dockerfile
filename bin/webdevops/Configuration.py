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
    'dockerPath': False,
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
        'toolImages': {},
        'serverspec': {
        },

        'env': {},
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
    'retry': 1,
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


class dotdictify(dict):
    def __init__(self, value=None):
        if value is None:
            pass
        elif isinstance(value, dict):
            for key in value:
                self.__setitem_internal__(key, value[key])
        else:
            raise TypeError, 'expected dict'

    def __setitem_internal__(self, key, value):
        """
        Set dict as raw value (preserv key with dots)
        """
        if isinstance(value, dict) and not isinstance(value, dotdictify):
            value = dotdictify(value)
        dict.__setitem__(self, key, value)

    def __setitem__(self, key, value):
        if key is not None and '.' in key:
            myKey, restOfKey = key.split('.', 1)
            target = self.setdefault(myKey, dotdictify())
            if not isinstance(target, dotdictify):
                raise KeyError, 'cannot set "%s" in "%s" (%s)' % (restOfKey, myKey, repr(target))
            target[restOfKey] = value
        else:
            if isinstance(value, dict) and not isinstance(value, dotdictify):
                value = dotdictify(value)
            dict.__setitem__(self, key, value)

    def __getitem__(self, key, raw=False):
        if key is None or '.' not in key or raw:
            return dict.get(self, key, None)
        myKey, restOfKey = key.split('.', 1)
        target = dict.get(self, myKey, None)
        if not isinstance(target, dotdictify):
            raise KeyError, 'cannot get "%s" in "%s" (%s)' % (restOfKey, myKey, repr(target))
        return target[restOfKey]

    def __contains__(self, key):
        """
        Check if element is contained in tree
        """
        if key is None or '.' not in key:
            return dict.__contains__(self, key)
        myKey, restOfKey = key.split('.', 1)
        if not dict.__contains__(self, myKey):
            return False
        target = dict.__getitem__(self, myKey)
        if not isinstance(target, dotdictify):
            return False
        return restOfKey in target

    def setdefault(self, key, default):
        """
        Set default value by using dotted notation
        """
        if key not in self:
            self[key] = default
        return self[key]

    def to_dict(self):
        """
        Convert to dict
        :return: dict
        """
        ret = {}
        for key in self:
            if key is not None:
                ret[key] = self.__getitem__(key, True)
        return ret

    def get(self, k, d=None):
        """
        Get element by using dotted notation
        """
        if dotdictify.__contains__(self, k):
            return dotdictify.__getitem__(self, k)
        return d

    def set(self, key, value):
        """
        Set value by using dotted notation
        """
        self[key] = value
        return self[key]

    __setattr__ = __setitem__
    __getattr__ = __getitem__
