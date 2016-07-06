#!/usr/bin/env/python
# -*- coding: utf-8 -*-

from cleo import Command, Output
from jinja2 import Environment, FileSystemLoader
from webdevops import Dockerfile
import os


class BuildDockerfileCommand(Command):
    """
    Build Dockerfile containers

    webdevops:build:dockerfile
        {--t|template=./template  :  path to the folder containing template }
        {--d|dockerfile=./docker : path to the folder containing dockerfile analyze}
        {--filter=?* : tags or images name }
    """

    template = ''

    template_header = '{% extends "Dockerfile/layout.jinja2" %}\n{% block content %}'
    template_footer = '{% endblock %}'

    def handle(self):
        template_path = os.path.abspath(self.option('template'))
        dockerfile_path = os.path.abspath(self.option('dockerfile'))
        filters = self.option('filter')

        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
            self.line('<info>-> </info><comment>docker path</comment> : %s' % dockerfile_path)
            self.line('<info>-> </info><comment>template path </comment> : %s' % template_path)
            self.option('filter')
            if 0 < len(filters):
                self.line('<info>-> </info><comment>filters </comment> :')
                for crit in filters:
                    self.line("\t * %s" % crit)

        self.template = Environment(
            autoescape=False,
            loader=FileSystemLoader([template_path]),
            trim_blocks=False
        )

        for file in Dockerfile.finder(dockerfile_path, "Dockerfile.jinja2", filters):
                self.process_dockerfile(file)

    def process_dockerfile(self, input_file):
        """
        :param input_file: Input File
        :type input_file: str
        """
        output_file = os.path.splitext(input_file)
        output_file = os.path.join(os.path.dirname(output_file[0]), os.path.basename(output_file[0]))

        docker_image = os.path.basename(os.path.dirname(os.path.dirname(output_file)))
        docker_tag = os.path.basename(os.path.dirname(output_file))

        context = {
            'Dockerfile': {
                'image': docker_image,
                'tag': docker_tag
            }
        }

        if Output.VERBOSITY_NORMAL <= self.output.get_verbosity():
            self.line("<info>* </info><comment>Processing Dockerfile for </comment>%s:%s" % (docker_image,docker_tag))

        with open(input_file, 'r') as fileInput:
            template_content = fileInput.read()

            template_content = self.template_header + template_content + self.template_footer

            rendered_content = self.template.from_string(template_content).render(context)
            rendered_content = rendered_content.lstrip()

            with open(output_file, 'w') as file_output:
                file_output.write(rendered_content)
