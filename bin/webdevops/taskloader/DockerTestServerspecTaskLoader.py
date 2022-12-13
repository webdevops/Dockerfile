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

import os, re, tempfile, json, base64
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

            #if dockerfile['dependency']:
            #    task['task_dep'].append('DockerTestServerspec|%s' % dockerfile['dependency'])

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

        # check if dockerfile is symlink, skipping tests if just a duplicate image
        # image is using the same hashes
        if dockerfile['image']['duplicate']:
            print '  Docker image %s is build from symlink and duplicate of %s' % (dockerfile['image']['fullname'], dockerfile['image']['from'])
            print '  -> skipping tests'
            BaseTaskLoader.set_task_status(task, 'skipped (symlink)', 'skipped')
            return True

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
        tmp_suffix = tmp_suffix.replace('/', '_')
        test_dockerfile = tempfile.NamedTemporaryFile(prefix='Dockerfile.', suffix=tmp_suffix, dir=configuration.get('serverspecPath'), bufsize=0, delete=False)

        # serverspec conf
        serverspec_conf = DockerTestServerspecTaskLoader.generate_serverspec_configuration(
            path=os.path.basename(test_dockerfile.name),
            dockerfile=dockerfile,
            configuration=configuration,
            is_toolimage=is_toolimage
        )

        # serverspec options
        serverspec_opts = []
        serverspec_opts.extend([spec_path, dockerfile['image']['fullname'], base64.b64encode(json.dumps(serverspec_conf)), os.path.basename(test_dockerfile.name)])

        # dockerfile content
        dockerfile_content = DockerTestServerspecTaskLoader.generate_dockerfile(
            dockerfile=dockerfile,
            configuration=configuration,
            is_toolimage=is_toolimage
        )

        # DryRun
        if configuration.get('dryRun'):
            if not os.path.isfile(spec_abs_path):
                print '                no tests found'

            print '         image: %s' % (dockerfile['image']['fullname'])
            print '          path: %s' % (spec_path)
            print '          args: %s' % (' '.join(serverspec_opts))
            print ''
            print 'spec configuration:'
            print '-------------------'
            print json.dumps(serverspec_conf, indent=4, sort_keys=True)
            print ''
            print 'Dockerfile:'
            print '-----------'
            print dockerfile_content
            return True

        # check if we have any tests
        if not os.path.isfile(spec_abs_path):
            print '         no tests defined (%s)' % (spec_path)
            BaseTaskLoader.set_task_status(task, 'skipped (no test)', 'skipped')
            return True

        # build rspec/serverspec command
        cmd = ['bash', 'serverspec.sh']
        cmd.extend(serverspec_opts)

        # create Dockerfile
        with open(test_dockerfile.name, mode='w', buffering=0) as f:
            f.write(dockerfile_content)
            f.flush()
            os.fsync(f.fileno())
            f.close()

        test_status = False
        for retry_count in range(0, configuration.get('retry')):
            try:
                test_status = Command.execute(cmd, cwd=configuration.get('serverspecPath'))
            except Exception as e:
                print e
                pass

            if test_status:
                break
            elif retry_count < (configuration.get('retry') - 1):
                print '    failed, retrying... (try %s)' % (retry_count + 1)
            else:
                print '    failed, giving up'

        return test_status

    @staticmethod
    def generate_serverspec_configuration(path, dockerfile, configuration, is_toolimage=False):
        """
        Generate serverspec configuration dict
        """
        ret = {}

        # add default vars
        default_env_list = configuration.get('dockerTest.configuration.default', False)
        if default_env_list:
            ret = default_env_list.to_dict().copy()

        # parse configuration by regexp
        image_configuration_regex = configuration.get('dockerTest.configuration.imageConfigurationRegex', False)
        if image_configuration_regex:
            parsed_configuration = ([m.groupdict() for m in image_configuration_regex.finditer(dockerfile['image']['fullname'])])
            if parsed_configuration:
                ret.update(parsed_configuration[0])

        # add docker image specific vars
        image_env_list = configuration.get('dockerTest.configuration.image')
        if image_env_list:
            image_env_list = image_env_list.to_dict().copy()
            for term in image_env_list:
                regex = r'.*%s.*' % term
                match = re.match(regex, dockerfile['image']['fullname'], re.IGNORECASE)
                if match:
                    for key in image_env_list[term]:
                        ret[key] = str(image_env_list[term][key])

        # add spec specific vars
        ret['DOCKER_IMAGE'] = dockerfile['image']['fullname']
        ret['DOCKER_TAG'] = dockerfile['image']['tag']
        ret['DOCKERFILE'] = path

        return ret


    @staticmethod
    def generate_dockerfile(dockerfile, configuration, is_toolimage=False):
        """
        Generate Dockerfile content
        """
        ret = []

        ret.append('FROM %s' % dockerfile['image']['fullname'])
        ret.append('COPY conf/ /')

        if is_toolimage:
            ret.append('RUN chmod +x /loop-entrypoint.sh')
            ret.append('ENTRYPOINT /loop-entrypoint.sh')

        for term in configuration.get('dockerTest.dockerfile', {}):
            if term in dockerfile['image']['fullname']:
                ret.extend( configuration.get('dockerTest.dockerfile').get(term))
        return '\n'.join(ret)

    @staticmethod
    def task_title(task):
        """
        Build task title function
        """
        return "Run serverspec for %s" % (BaseTaskLoader.human_task_name(task.name))
