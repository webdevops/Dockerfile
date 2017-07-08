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
from webdevops import Dockerfile
import os
from distutils.dir_util import copy_tree, remove_tree
from threading import Thread
import Queue
import shutil


class Provisioner(Thread):
    """
    Asked to provisioning an image
    """

    image_name = ''

    image_config = {}

    output = ''

    dockerfile = ''

    provision = ''

    queue = ''

    # def __init__(self, dockerfile, provision, image_name, image_config, output):
    def __init__(self, dockerfile, provision, queue, output):
        """
        Construct

        :param dockerfile: path to the folder containing dockerfile analyze
        :type dockerfile: str

        :param queue: Stack of image config
        :type queue: Queue.Queue

        :param output: stdout
        :type output: Output

        """
        Thread.__init__(self)
        self.dockerfile = dockerfile
        self.provision = provision
        self.queue = queue
        self.output = output

    def __get_item(self):
        """ Get a item in the Queue """
        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
            self.line('<comment>Looking for the next item</comment>')
        item = self.queue.get(True, 5.0)
        self.image_name = item['image_name']
        self.image_config = item['image_config']

    def __done_item(self):
        """ Set current item of the Queue to done """
        self.queue.task_done()
        self.image_name = ''
        self.image_config = {}

    def run(self):
        while True:
            try:
                self.__get_item()
                if Output.VERBOSITY_NORMAL <= self.output.get_verbosity():
                    self.line(
                        '<fg=blue;options=bold>Building configuration for </>webdevops/%s' % self.image_name
                    )
                self.__clear_configuration()
                if 'configuration' in self.image_config:
                    self.__deploy_configuration()
                self.__done_item()
            except Queue.Empty:
                if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
                    self.line("<fg=magenta>End</>")
                break

    def __clear_configuration(self):
        """
        Remove the old configuration
        """
        dockerfiles = Dockerfile.find_by_image_and_tag(self.dockerfile, self.image_name, '*')
        dockerfiles = [os.path.dirname(image_path) for image_path in dockerfiles]
        for dest in dockerfiles:
            dest = os.path.abspath(os.path.join(dest, 'conf/'))
            if os.path.exists(dest):
                if Output.VERBOSITY_VERY_VERBOSE <= self.output.get_verbosity():
                    self.line('<fg=red>delete configuration :</> %s' % dest)
                remove_tree(dest, 0)

    def __deploy_configuration(self):
        """
        Deploy the configuration to the container
        """
        for src, tag in self.image_config['configuration'].iteritems():
            if Output.VERBOSITY_NORMAL <= self.output.get_verbosity():
                self.line("<fg=cyan>%s</> <info>=></info> %s:%s" % (src, self.image_name, tag))
            if isinstance(tag, list):
                dockerfiles = []
                for t in tag:
                    dockerfiles.extend(Dockerfile.find_by_image_and_tag(self.dockerfile, self.image_name, t))
            else:
                dockerfiles = Dockerfile.find_by_image_and_tag(self.dockerfile, self.image_name, tag)
            dockerfiles = [os.path.dirname(image_path) for image_path in dockerfiles]
            self.__copy_configuration(dockerfiles, src)


    def __copy_configuration(self, dockerfiles, src):
        """
        Copy the different configs to the container

        :param dockerfiles: List of path's images to provisioning
        :type dockerfiles: list

        :param src: sub-path of the provisioning directory
        :type src: str
        """
        src = os.path.abspath(os.path.join(self.provision, src)) + "/."
        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
            self.line('<comment>src :</comment> %s' % src)
        for dest in dockerfiles:
            dest = os.path.abspath(os.path.join(dest, 'conf/'))
            if not os.path.exists(dest):
                if Output.VERBOSITY_DEBUG <= self.output.get_verbosity():
                    self.line('<comment>create :</comment> %s' % dest)
                os.mkdir(dest)
            if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
                self.line('<comment>dest :</comment> %s' % dest)
            copy_tree(src, dest, 1, 1, 0, 0, 0)

    def line(self, msg):
        self.output.writeln(
            '<fg=magenta>({0:^8}) </><fg=cyan>[{1:.<20}]</> {2}'.format(self.name, self.image_name, msg)
        )
