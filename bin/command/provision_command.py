#!/usr/bin/env/python
# -*- coding: utf-8 -*-

from cleo import Command, Output
from webdevops import Provisioner
import os
import yaml
import yamlordereddictloader
import time
import Queue
import shutil

from pprint import pprint


class ProvisionCommand(Command):
    """
    Provisionning docker images

    webdevops:provision
        {--p|provision=./provisioning : path output}
        {--d|dockerfile=./docker : path to the folder containing dockerfile analyze}
        {--image=?* : filter on images name }
        {--baselayout : Build the baselayout}
        {--t|thread=3 (integer): Number of threads to run }
    """

    conf = ''

    __threads = []

    __queue = ''

    def handle(self):
        start = time.time()
        self.__queue = Queue.Queue()
        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
            self.line('<info>provision :</info> %s' % self.option('provision'))
            self.line('<info>dockerfile :</info> %s' % self.option('dockerfile'))
            self.line('<info>baselayout :</info> %s' % self.option('baselayout'))
            self.line('<info>thread :</info> %d' % self.option('thread'))
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
        end = time.time()
        print("elapsed time : %d second" % (end - start))

    def __create_thread(self):
        for i in range(self.option('thread')):
            thread_name = "Pixie_%d" % i
            if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
                self.line("<info>*</info> -> Create thread <fg=magenta>%s</>" % thread_name)
            provisioner = Provisioner.Provisioner(
                self.option('dockerfile'),
                self.option('provision'),
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
        stream = open(os.path.dirname(__file__) + "/../../conf/provision.yml", "r")
        self.conf = yaml.load(stream, Loader=yamlordereddictloader.Loader)

    def __build_base_layout(self):
        """
        Build tar file from _localscripts for bootstrap containers
        """
        if self.option('baselayout'):
            if Output.VERBOSITY_NORMAL <= self.output.get_verbosity():
                self.line('<info>* </info> Building localscipts')
            base_path = os.path.abspath(os.path.dirname(__file__) +"/../../baselayout/")
            shutil.make_archive('baselayout', 'bztar', base_path)
            os.rename('baselayout.tar.bz2', 'baselayout.tar')



