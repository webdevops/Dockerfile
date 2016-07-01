#!/usr/bin/env/python
# -*- coding: utf-8 -*-

from cleo import Command, Output
from webdevops import Dockerfile
import os
import yaml
import yamlordereddictloader
from distutils.dir_util import copy_tree, remove_tree
from pprint import pprint


class ProvisionCommand(Command):
    """
    Provisionning docker images

    webdevops:provision
        {--p|provision=./provisioning : path output}
        {--d|dockerfile=./docker : path to the folder containing dockerfile analyze}
        {--image=?* : filter on images name }
        {--bootstrap : Provision the bootstrap}
    """

    conf = ''

    def handle(self):
        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
            self.line('<info>provision :</info> %s' % self.option('provision'))
            self.line('<info>dockerfile :</info> %s' % self.option('dockerfile'))
            if 0 < len(self.option('image')):
                self.line('<info>-> </info><comment>images </comment> :')
                for crit in self.option('image'):
                    self.line("\t * %s" % crit)
        self.__load_configuration()
        for image_name in self.conf['provision']:
            if 0 == len(self.option('image')) or image_name in self.option('image'):
                if Output.VERBOSITY_NORMAL <= self.output.get_verbosity():
                    self.line('<info>-> </info><comment>Building configuration for </comment>webdevops/%s' % image_name)
                self.__clear_configuration(image_name)
                if 'configuration' in self.conf['provision'][image_name]:
                    self.__deploy_configuration(image_name, self.conf['provision'][image_name]['configuration'])

    def __load_configuration(self):
        """
        Load the configuration for provisioning image
        """
        stream = open(os.path.dirname(__file__) + "/../../conf/provision.yml", "r")
        self.conf = yaml.load(stream, Loader=yamlordereddictloader.Loader )

    def __clear_configuration(self, image_name):
        """
        Remove the old configuration

        :param image_name: the name of the image
        :type image_name: str
        """
        dockerfiles = Dockerfile.find_by_image_and_tag(self.option('dockerfile'), image_name, '*')
        dockerfiles = [os.path.dirname(image_path) for image_path in dockerfiles]
        for dest in dockerfiles:
            dest = os.path.abspath(os.path.join(dest,'conf/'))
            if os.path.exists(dest):
                if Output.VERBOSITY_VERY_VERBOSE <= self.output.get_verbosity():
                    self.line('\t\t<info>*</info> <comment>delete configuration :</comment> %s' % dest)
                remove_tree(dest, 0)
            else:
                self.line('<error>Warning</error> : file not exist %s' % dest)

    def __deploy_configuration(self, image_name, configuration):
        """ Deploy the configuration to the image

        Clear and copy provisionning to the image

        :param image_name: the name of the image
        :type image_name: str

        :param configuration: list of configuration to applied in the image
        :type configuration: list
        """
        for src, tag in configuration.iteritems():
            if Output.VERBOSITY_NORMAL <= self.output.get_verbosity():
                self.line("\t * %s <info>=></info> %s:%s" % (src, image_name, tag))

            if isinstance(tag, list):
                dockerfiles= []
                for t in tag:
                    dockerfiles.extend(Dockerfile.find_by_image_and_tag(self.option('dockerfile'), image_name, t))
            else:
                dockerfiles = Dockerfile.find_by_image_and_tag(self.option('dockerfile'), image_name, tag)
            dockerfiles = [os.path.dirname(image_path) for image_path in dockerfiles]
            self.__copy_configuration(dockerfiles, src)

    def __copy_configuration(self, dockerfiles, src):
        """
        Copy the different configs to the images

        :param dockerfiles: List of path's images to provisioning
        :type dockerfiles: list

        :param src: sub-path of the provisioning directory
        :type src: str
        """
        src = os.path.abspath(os.path.join(self.option('provision'), src))+"/."
        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
                self.line('\t\t<info>*</info> <comment>src :</comment> %s' % src)
        for dest in dockerfiles:
            dest = os.path.abspath(os.path.join(dest,'conf/'))
            if not os.path.exists(dest):
                if Output.VERBOSITY_DEBUG <= self.output.get_verbosity():
                    self.line('\t\t<comment> -> create : %s</comment>' % dest)
                os.mkdir(dest)
            if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
                self.line('\t\t<info>*</info> <comment>dest :</comment> %s' % dest)
            copy_tree(src, dest, 1, 1, 0, 0, 0)

