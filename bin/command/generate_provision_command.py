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
import yaml
import yamlordereddictloader
import time
import Queue
import shutil
import grp
from cleo import Output
from webdevops import Provisioner
from webdevops.command import BaseCommand

class GenerateProvisionCommand(BaseCommand):
    """
    Provisionning docker images

    generate:provision
        {--image=?*              : filter on images name }
        {--baselayout            : Build the baselayout }
        {--t|threads=0           : threads}
    """

    conf = ''

    __threads = []

    __queue = ''

    def run_task(self, configuration):
        self.__queue = Queue.Queue()
        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
            self.line('<info>provision :</info> %s' % configuration.get('provisionPath'))
            self.line('<info>dockerfile :</info> %s' % configuration.get('dockerPath'))
            self.line('<info>baselayout :</info> %s' % self.option('baselayout'))
            if 0 < len(self.option('image')):
                self.line('<info>images </info> :')
                for crit in self.option('image'):
                    self.line("\t * %s" % crit)
        self.__load_configuration()
        self.__build_base_layout()
        self.__create_thread()
        for image_name in self.conf['provision']:
            if 0 == len(self.option('image')) or image_name in self.option('image'):
                self.__queue.put({'image_name': image_name, 'image_config': self.conf['provision'][image_name]})
        self.__queue.join()
        if os.path.exists('baselayout.tar'):
            os.remove('baselayout.tar')

    def __create_thread(self):
        for i in range(self.get_threads()):
            thread_name = "Pixie_%d" % i
            if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
                self.line("<info>*</info> -> Create thread <fg=magenta>%s</>" % thread_name)
            provisioner = Provisioner(
                self.configuration.get('dockerPath'),
                self.configuration.get('provisionPath'),
                self.__queue,
                self.output
            )
            provisioner.setDaemon(True)
            provisioner.setName(thread_name)
            provisioner.start()
            # self.__threads.append(provisioner)

    def __load_configuration(self):
        """
        Load the configuration for provisioning image
        """
        configuration_file = os.path.join(self.configuration.get('confPath'), 'provision.yml')
        stream = open(configuration_file, "r")
        self.conf = yaml.load(stream, Loader=yamlordereddictloader.Loader)

    def __build_base_layout(self):
        """
        Build tar file from _localscripts for bootstrap containers
        """
        if self.option('baselayout'):
            if Output.VERBOSITY_NORMAL <= self.output.get_verbosity():
                self.line('<info>* </info> Building localscipts')
            base_path = self.configuration.get('baselayoutPath')

            root_group = grp.getgrgid(0)

            shutil.make_archive(
                base_name='baselayout',
                format='bztar',
                root_dir=base_path,
                owner='root',
                group=root_group.gr_name
            )
            os.rename('baselayout.tar.bz2', 'baselayout.tar')



