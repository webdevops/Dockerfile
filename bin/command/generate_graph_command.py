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
import yaml
from datetime import date
from graphviz import Digraph
from cleo import Output
from webdevops import DockerfileUtility
from webdevops.command import BaseCommand
from cleo.validators import Enum

class GenerateGraphCommand(BaseCommand):
    """
    Generate a diagram of container

    generate:graph
        {--a|all : Show all informations}
        {--o|output= : path output}
        {--F|format=png (choice) : output format }
        {--f|filename=docker-image-layout.gv :  file output}
    """

    validation = {
        '--format': Enum(['png', 'jpg', 'pdf', 'svg'])
    }

    from_regex = re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>.+))?', re.MULTILINE)

    containers = {}

    tags = {}

    subgraph = {}

    edges = {}

    conf = ''

    def run_task(self, configuration):
        if self.option('output'):
            configuration.set('imagePath', self.option('output'))

        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
            self.line('<info>ALL :</info> %s' % self.option('all'))
            self.line('<info>output :</info> %s' % configuration.get('imagePath'))
            self.line('<info>format :</info> %s' % self.option('format'))
            self.line('<info>dockerPath :</info> %s' % configuration.get('dockerPath'))
            self.line('<info>filename :</info> %s' % self.option('filename'))
        self.__load_configuration()

        dockerfileList = DockerfileUtility.find_dockerfiles_in_path(
            base_path=configuration.get('dockerPath'),
            path_regex=configuration.get('docker.pathRegex'),
            image_prefix=configuration.get('docker.imagePrefix'),
        )

        for dockerfile in dockerfileList:
            self.__process_dockerfile(dockerfile)

        dia = self.build_graph()
        dia.render()

    def __process_dockerfile(self, dockerfile):
        """

        :param dockerfile_full_path:
        :type dockerfile_full_path: str

        :return: self
        :rtype: self
        """

        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
            self.line('<info>-> Processing: </info>%s' % dockerfile['image']['fullname'])

        docker_image = dockerfile['image']['name']
        parent_image_name = DockerfileUtility.image_basename(dockerfile['image']['from'])
        parent_image_tag  = DockerfileUtility.extract_image_name_tag(dockerfile['image']['from'])

        if not parent_image_name in self.containers:
            self.containers[parent_image_name] = 'scratch'
            self.__append_tag(parent_image_name, 'latest')

        self.containers[docker_image] = parent_image_name
        self.__append_tag(docker_image, parent_image_tag)

        return self

    def __append_tag(self, docker_image, tag):
        """

        :param docker_image:
        :type docker_image: str

        :param tag:
        :type tag: str

        :return: self
        """
        if not self.tags.has_key(docker_image):
            self.tags[docker_image] = {}
        self.tags[docker_image][tag] = tag
        return self

    def __get_graph(self, default_graph, name):
        """

        :param default_graph: Main diagram
        :type default_graph: Digraph

        :param name: Name of the sub-diagram
        :type name: str

        :return: the selected diagram
        :rtype: Digraph
        """
        for group, group_attr in self.conf['diagram']['groups'].items():
            for dockerRegex in group_attr['docker']:
                if re.match(dockerRegex, name):
                    return group, self.subgraph[group]
        return '__root__', default_graph

    def __load_configuration(self):
        conf_file_path = os.path.join(self.configuration.get('confPath'), 'diagram.yml')

        stream = open(conf_file_path, 'r')
        self.conf = yaml.safe_load(stream)

    def __apply_styles(self, graph, styles):
        """

        :param graph: Diagraph to apply styles
        :type graph Digraph

        :param styles: Styles properties of Diagraph
        :type styles: dict

        :return: The GRaph with Style
        :rtype: Digraph
        """
        graph.graph_attr.update(
            ('graph' in styles and styles['graph']) or {}
        )
        graph.node_attr.update(
            ('nodes' in styles and styles['nodes']) or {}
        )
        graph.edge_attr.update(
            ('edges' in styles and styles['edges']) or {}
        )
        return graph

    def build_graph(self):
        """

        :return: the graph
        :rtype: Digraph
        """
        dia = Digraph('webdevops',
                      filename=self.option('filename'),
                      format=self.option('format'),
                      directory=self.configuration.get('imagePath')
                    )
        dia = self.__apply_styles(dia, self.conf['diagram']['styles'])

        label = r'label = "\n\n%s"' % self.configuration.get('graph.label')

        dia.body.append(label % date.today().strftime("%Y-%m-%d"))
        dia.body.append('newrank=true;')

        rank_image_list = {}
        rank_group_list = {}

        # Create subgraph
        for group, group_attr in self.conf['diagram']['groups'].items():
            self.subgraph[group] = Digraph('cluster_%s' % group)
            self.subgraph[group].body.append(r'label = "%s"' % group_attr['name'])
            self.subgraph[group] = self.__apply_styles(self.subgraph[group], group_attr['styles'])

            if 'rank' in group_attr:
                rank_group_list[group] = group_attr['rank']

        for image, base in self.containers.items():
            group_image, graph_image = self.__get_graph(dia, image)
            group_base, graph_base = self.__get_graph(dia, base)
            if not "scratch" in base:
                if graph_image == graph_base:
                    graph_image.edge(base, image)
                else:
                    graph_image.node(image)
                    self.edges[image] = base
                if self.option('all'):
                    self.__attach_tag(graph_image, image)
            else:
                graph_image.node(image)

            if group_image in rank_group_list:
                image_rank = rank_group_list[group_image]

                if not image_rank in rank_image_list:
                    rank_image_list[image_rank] = []

                rank_image_list[image_rank].append(image)

        # add repositories (subgraph/cluster)
        for name, subgraph in self.subgraph.items():
            dia.subgraph(subgraph)

        # add images (node)
        for image, base in self.edges.items():
            dia.edge(base, image)

        # add invisible constraints to add ranked groups
        for rank, imagelist in rank_image_list.items():
            rank_next = rank + 1

            if rank_next in rank_image_list:
                imagelist_next = rank_image_list[rank_next]

                for image in imagelist:
                    for image_next in imagelist_next:
                        dia.body.append('{ "%s" -> "%s" [style=invis] }' % (image, image_next))

        return dia

    def __attach_tag(self, graph, image):
        for tag in self.tags[image]:
            node_name = "%s-%s" % (image, tag)
            graph.node(node_name, label=tag, fillcolor='#eeeeee', shape='folder')
            graph.edge(image, node_name)
