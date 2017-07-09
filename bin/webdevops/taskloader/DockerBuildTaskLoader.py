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

import os, sys, re, copy, time
from .BaseTaskLoader import BaseTaskLoader
from .BaseDockerTaskLoader import BaseDockerTaskLoader
from webdevops import DockerfileUtility

class DockerBuildTaskLoader(BaseDockerTaskLoader):

    def generate_task_list(self, dockerfileList):
        """
        Generate task list for docker build
        """
        tasklist = []

        # TASK: dependency puller
        task = {
            'name': 'DockerBuild|DependencyPuller',
            'title': DockerBuildTaskLoader.task_title_dependency_puller,
            'actions': [(BaseTaskLoader.task_runner,
                         [DockerBuildTaskLoader.task_dependency_puller, [self.docker_client, dockerfileList, self.configuration]])],
            'task_dep': []
        }
        tasklist.append(task)

        # TASK: dockerfile build
        for dockerfile in dockerfileList:
            task = {
                'name': 'DockerBuild|%s' % dockerfile['image']['fullname'],
                'title': DockerBuildTaskLoader.task_title,
                'actions': [(BaseTaskLoader.task_runner, [DockerBuildTaskLoader.task_run, [self.docker_client, dockerfile, self.configuration]])],
                'task_dep': ['DockerBuild|DependencyPuller']
            }

            for dep in dockerfile['dependency']:
                task['task_dep'].append('DockerBuild|%s' % dep)


            tasklist.append(task)

        # TASK: finisher
        # task = {
        #     'name': 'FinishChain|DockerBuild',
        #     'title': DockerBuildTaskLoader.task_title_finish,
        #     'actions': [(DockerBuildTaskLoader.action_chain_finish, ['docker build'])],
        #     'task_dep': [task.name for task in taskList]
        # }
        # tasklist.append(task)

        return tasklist

    @staticmethod
    def task_dependency_puller(docker_client, dockerfileList, configuration, task):
        """
        Pulls dependency images before building
        """
        def pull_image(image):
            print ' -> Pull base image %s ' % image

            if configuration.get('dryRun'):
                return True

            pull_image_name = DockerfileUtility.image_basename(image)
            pull_image_tag = DockerfileUtility.extract_image_name_tag(image)

            pull_status = False
            for retry_count in range(0, configuration.get('retry')):
                pull_status = docker_client.pull_image(
                    name=pull_image_name,
                    tag=pull_image_tag,
                )

                if pull_status:
                    break
                elif retry_count < (configuration.get('retry') - 1):
                    print '    failed, retrying... (try %s)' % (retry_count + 1)
                else:
                    print '    failed, giving up'

            if not pull_status:
                return False

            return True

        image_list = []
        for dockerfile in dockerfileList:
            # Pull base image (FROM: xxx) first
            if DockerfileUtility.check_if_base_image_needs_pull(dockerfile['image']['from'], configuration):
                image_list.append(dockerfile['image']['from'])

            # Pull straged images (multi-stage dockerfiles)
            for multiStageImage in dockerfile['image']['multiStageImages']:
                if DockerfileUtility.check_if_base_image_needs_pull(multiStageImage, configuration):
                    image_list.append(multiStageImage)

        # filter only unique image names
        image_list = set(image_list)

        # pull images
        for image in set(image_list):
            if not pull_image(image):
                return False

        return True

    @staticmethod
    def task_run(docker_client, dockerfile, configuration, task):
        """
        Build one Dockerfile
        """

        # check if dockerfile is symlink, skipping tests if just a duplicate image
        # image is using the same hashes
        if dockerfile['image']['duplicate'] and not task.task_dep:
            print '  Docker image %s is build from symlink but not included in build chain, please include %s' % (dockerfile['image']['fullname'], dockerfile['image']['from'])
            print '  -> failing build'
            return False

        if configuration.get('dryRun'):
            print '      path: %s' % dockerfile['path']
            print '       dep: %s' % (DockerBuildTaskLoader.human_task_name_list(task.task_dep) if task.task_dep else 'none')
            return True

        ## Build image
        print ' -> Building image %s ' % dockerfile['image']['fullname']
        build_status = False
        for retry_count in range(0, configuration.get('retry')):
            build_status = docker_client.build_dockerfile(
                path=dockerfile['path'],
                name=dockerfile['image']['fullname'],
                nocache=configuration.get('dockerBuild.noCache'),
            )

            if build_status:
                break
            elif retry_count < (configuration.get('retry')-1):
                print '    failed, retrying... (try %s)' % (retry_count+1)
            else:
                print '    failed, giving up'

        if build_status and dockerfile['image']['duplicate']:
            BaseTaskLoader.set_task_status(task, 'finished (duplicate)', 'success2')

        return build_status

    @staticmethod
    def task_title(task):
        """
        Build task title function
        """
        return "Docker build %s" % (BaseTaskLoader.human_task_name(task.name))

    @staticmethod
    def task_title_dependency_puller(task):
        """
        Build task title function
        """
        return "Pulling dependency images"
