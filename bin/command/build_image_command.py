#! /usr/bin/env python

from cleo import Command, Output
from jinja2 import Environment, FileSystemLoader
from webdevops import Dockerfile
from webdevops import DockerBuildTaskLoader
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
                    'autoLatestTag': 'ubuntu-16.04'
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



