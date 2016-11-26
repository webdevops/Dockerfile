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

import os, re, tempfile, json
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
                'name': 'DockerTestServerspec|%s' % dockerfile['image']['fullname'],
                'title': DockerTestServerspecTaskLoader.task_title,
                'actions': [(BaseTaskLoader.task_runner, [DockerTestServerspecTaskLoader.task_run, [dockerfile, self.configuration]])],
                'task_dep': []
            }
            tasklist.append(task)

        # task = {
        #     'name': 'FinishChain|DockerTestServerspec',
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

        # Check if current image is a toolimage (no daemon)
        is_toolimage = False
        if 'serverspec' in configuration and 'toolImages' in configuration['serverspec']:
            for term in configuration['serverspec']['toolImages']:
                if term in dockerfile['image']['fullname']:
                    is_toolimage = True

        # rspec spec file settings
        spec_file = '%s_spec.rb' % dockerfile['image']['imageName']
        spec_path = os.path.join('spec', 'docker', spec_file)
        spec_abs_path = os.path.join(configuration['serverspecPath'], spec_path)

        # create dockerfile
        tmp_suffix = '.%s_%s_%s.tmp' % (dockerfile['image']['repository'], dockerfile['image']['imageName'], dockerfile['image']['tag'])
        test_dockerfile = tempfile.NamedTemporaryFile(prefix='Dockerfile.', suffix=tmp_suffix, dir=configuration['serverspecPath'], bufsize=0, delete=False)

        # serverspec options
        serverspec_opts = []
        serverspec_opts.extend(['--pattern', spec_path])

        # serverspec env
        serverspec_env = {}
        if 'serverspec' in configuration and 'env' in configuration['serverspec']:
            for term in configuration['serverspec']['env']:
                if term in dockerfile['image']['fullname']:
                    for key in configuration['serverspec']['env'][term]:
                        serverspec_env[key] = configuration['serverspec']['env'][term][key]
        serverspec_env['DOCKER_IMAGE'] = dockerfile['image']['fullname']
        serverspec_env['DOCKERFILE'] = os.path.basename(test_dockerfile.name)

        # DryRun
        if configuration['dryRun']:
            if not os.path.isfile(spec_abs_path):
                print '                no tests found'

            print '         image: %s' % (dockerfile['image']['fullname'])
            print '          path: %s' % (spec_path)
            print '          args: %s' % (' '.join(serverspec_opts))
            print '   environment:'
            print json.dumps(serverspec_env, indent=4, sort_keys=True)

            os.remove(test_dockerfile.name)
            return True

        # check if we have any tests
        if not os.path.isfile(spec_abs_path):
            print '         no tests defined (%s)' % (spec_path)
            return True

        # build rspec/serverspec command
        cmd = ['bundle', 'exec', 'rspec']
        cmd.extend(serverspec_opts)

        # Set environment variables
        env = os.environ.copy()
        env.update(serverspec_env)

        # create Dockerfile
        with open(test_dockerfile.name, mode='w', buffering=0) as f:
            f.write('FROM %s\n' % dockerfile['image']['fullname'])
            f.write('COPY conf/ /\n')

            if is_toolimage:
                f.write('RUN chmod +x /loop-entrypoint.sh\n')
                f.write('ENTRYPOINT /loop-entrypoint.sh\n')
            f.flush()
            f.close()

        try:
            # Execute test
            ret = GeneralUtility.cmd_execute(cmd, cwd=configuration['serverspecPath'], env=env)
        except Exception as e:
            os.remove(test_dockerfile.name)
            raise e

        os.remove(test_dockerfile.name)

        return ret

    @staticmethod
    def task_title(task):
        """
        Build task title function
        """
        return "Run serverspec for %s" % (BaseTaskLoader.human_task_name(task.name))
