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
from webdevops import Command
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
        for term in configuration.get('dockerTest.toolImages', {}):
            if term in dockerfile['image']['fullname']:
                is_toolimage = True

        # rspec spec file settings
        spec_path = configuration.get('dockerTest.serverspec.specPath') % dockerfile['image']['imageName']
        spec_abs_path = os.path.join(configuration.get('serverspecPath'), spec_path)

        # create dockerfile
        tmp_suffix = '.%s_%s_%s.tmp' % (dockerfile['image']['repository'], dockerfile['image']['imageName'], dockerfile['image']['tag'])
        test_dockerfile = tempfile.NamedTemporaryFile(prefix='Dockerfile.', suffix=tmp_suffix, dir=configuration.get('serverspecPath'), bufsize=0, delete=False)

        # serverspec options
        serverspec_opts = []
        serverspec_opts.extend(['--pattern', spec_path])

        # serverspec env
        serverspec_env = {}
        dockertest_env_condition_list = configuration.get('dockerTest.env', {}).to_dict()
        for term in dockertest_env_condition_list:
            if term in dockerfile['image']['fullname']:
                for key in dockertest_env_condition_list[term]:
                    serverspec_env[key] = str(dockertest_env_condition_list[term][key])
        serverspec_env['DOCKER_IMAGE'] = dockerfile['image']['fullname']
        serverspec_env['DOCKER_TAG'] = dockerfile['image']['tag']
        serverspec_env['DOCKERFILE'] = os.path.basename(test_dockerfile.name)

        # dockerfile content
        dockerfile_content = []
        dockerfile_content.append('FROM %s' % dockerfile['image']['fullname'])
        dockerfile_content.append('COPY conf/ /')

        if is_toolimage:
            dockerfile_content.append('RUN chmod +x /loop-entrypoint.sh')
            dockerfile_content.append('ENTRYPOINT /loop-entrypoint.sh')

        for term in configuration.get('dockerTest.dockerfile', {}):
            if term in dockerfile['image']['fullname']:
                dockerfile_content.extend( configuration.get('dockerTest.dockerfile').get(term))

        # DryRun
        if configuration.get('dryRun'):
            if not os.path.isfile(spec_abs_path):
                print '                no tests found'

            print '         image: %s' % (dockerfile['image']['fullname'])
            print '          path: %s' % (spec_path)
            print '          args: %s' % (' '.join(serverspec_opts))
            print ''
            print 'environment:'
            print '------------'
            print json.dumps(serverspec_env, indent=4, sort_keys=True)
            print ''
            print 'Dockerfile:'
            print '-----------'
            print '\n'.join(dockerfile_content)

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
            f.write('\n'.join(dockerfile_content))
            f.flush()
            f.close()

        test_status = False
        for retry_count in range(0, configuration.get('retry')):
            try:
                test_status = Command.execute(cmd, cwd=configuration.get('serverspecPath'), env=env)
            except Exception:
                pass

            if test_status:
                break
            elif retry_count < (configuration.get('retry') - 1):
                print '    failed, retrying... (try %s)' % (retry_count + 1)
            else:
                print '    failed, giving up'

        os.remove(test_dockerfile.name)
        return test_status

    @staticmethod
    def task_title(task):
        """
        Build task title function
        """
        return "Run serverspec for %s" % (BaseTaskLoader.human_task_name(task.name))
