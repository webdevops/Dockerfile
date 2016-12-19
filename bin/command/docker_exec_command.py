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

from cleo import Output
from termcolor import colored
from webdevops.command import DoitCommand
from webdevops.taskloader import DockerPushTaskLoader
from webdevops import Command, DockerfileUtility

class DockerExecCommand(DoitCommand):
    """
    Push images to registry/hub

    docker:exec
        {docker command*         : Command}
        {--dry-run               : show only which images will be build}
        {--whitelist=?*          : image/tag whitelist }
        {--blacklist=?*          : image/tag blacklist }
    """

    def run_task(self, configuration):
        dockerfile_list = DockerfileUtility.find_dockerfiles_in_path(
            base_path=configuration.get('dockerPath'),
            path_regex=configuration.get('docker.pathRegex'),
            image_prefix=configuration.get('docker.imagePrefix'),
            whitelist=configuration.get('whitelist'),
            blacklist=configuration.get('blacklist'),
        )

        image_fail_list = []

        docker_command = self.argument('docker command')

        for dockerfile in dockerfile_list:
            title = dockerfile['image']['fullname']

            print title
            print '~' * len(title)

            if configuration['dryRun']:
                print '  exec: %s' % (docker_command)
            else:

                cmd = [
                    'docker',
                    'run',
                    '-t',
                    '--rm',
                    dockerfile['image']['fullname'],
                    'bash',
                    '-c',
                    '%s' % (' '.join(docker_command))
                ]
                status = Command.execute(cmd)

                if status:
                    print colored(' -> successfull', 'green')
                else:
                    print colored(' -> failed', 'red')
                    image_fail_list.append(dockerfile['image']['fullname'])
            print ''
            print ''

        if len(image_fail_list) >= 1:
            print ''
            print colored(' => failed images (%s):' % (str(len(image_fail_list))), 'red')
            for image in image_fail_list:
                print '   - %s ' % image
            print ''

            return False
        else:
            return True




