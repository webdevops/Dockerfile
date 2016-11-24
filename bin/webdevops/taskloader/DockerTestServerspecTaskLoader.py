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
import tempfile
from webdevops import GeneralUtility
from .BaseDockerTaskLoader import BaseDockerTaskLoader
from .BaseTaskLoader import BaseTaskLoader
from doit.task import dict_to_task
import pytest

class DockerTestServerspecTaskLoader(BaseDockerTaskLoader):

    def generate_task_list(self, dockerfile_list):
        """
        Generate task list for docker push
        """
        tasklist = []

        for dockerfile in dockerfile_list:
            task = {
                'name': 'DockerTest|%s' % dockerfile['image']['fullname'],
                'title': DockerTestServerspecTaskLoader.task_title,
                'actions': [(BaseTaskLoader.task_runner, [DockerTestServerspecTaskLoader.task_run, [dockerfile, self.configuration]])],
                'task_dep': []
            }
            tasklist.append(task)

        # task = {
        #     'name': 'FinishChain|DockerTest',
        #     'title': DockerTestTestinfraTaskLoader.task_title_finish,
        #     'actions': [(DockerTestTestinfraTaskLoader.action_chain_finish, ['docker test'])],
        #     'task_dep': [task.name for task in taskList]
        # }
        # tasklist.append(task)

        return tasklist

    @staticmethod
    def task_run(dockerfile, configuration, task):
        """
        Run test
        """
        is_toolimage = False

        if 'serverspec' in configuration and 'toolImages' in configuration['serverspec']:
            for term in configuration['serverspec']['toolImages']:
                if term in dockerfile['image']['fullname']:
                    is_toolimage = True


        serverspec_opts = []

        spec_file = '%s_spec.rb' % dockerfile['image']['imageName']
        spec_path = os.path.join('spec', 'docker', spec_file)

        serverspec_opts.extend(['--pattern', spec_path])

        if configuration['dryRun']:
            print '         image: %s' % (dockerfile['image']['fullname'])
            print '          path: %s' % (spec_path)
            print '          args: %s' % (' '.join(serverspec_opts))
            return True

        cmd = ['bundle', 'exec', 'rspec']
        cmd.extend(serverspec_opts)

        # create dockerfile
        test_dockerfile = tempfile.NamedTemporaryFile(prefix='Dockerfile.', dir=configuration['serverspecPath'])

        with open(test_dockerfile.name, 'w') as f:
            f.write('FROM %s\n' % dockerfile['image']['fullname'])
            f.write('COPY conf/ /\n')

            if is_toolimage:
                f.write('RUN chmod +x /loop-entrypoint.sh\n')
                f.write('ENTRYPOINT /loop-entrypoint.sh\n')
            f.close()

            # Set environment variables
            env = os.environ.copy()
            if 'serverspec' in configuration and 'env' in configuration['serverspec']:
                for term in configuration['serverspec']['env']:
                    if term in dockerfile['image']['fullname']:
                        env.update(configuration['serverspec']['env'][term])
            env['DOCKER_IMAGE'] = dockerfile['image']['fullname']
            env['DOCKERFILE'] = os.path.basename(test_dockerfile.name)

            ret = GeneralUtility.cmd_execute(cmd, cwd=configuration['serverspecPath'], env=env)

        return ret

    @staticmethod
    def task_title(task):
        """
        Build task title function
        """
        return "Run serverspec for %s" % (BaseTaskLoader.human_task_name(task.name))
