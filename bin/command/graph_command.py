#!/usr/bin/env/python
# -*- coding: utf-8 -*-

from cleo import Command, Output
from cleo.validators import Enum
import os
import re
from graphviz import Digraph
import yaml
from datetime import date


class GraphCommand(Command):
    """
    Generate a diagram of container

    webdevops:graph
        {--a|all : Show all informations}
        {--p|path= : path output}
        {--F|format=png (choice) : output format }
        {--d|dockerfile= : path to the folder containing dockerfile analyze}
        {--f|filename=webdevops.gv :  file output}
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

    def handle(self):
        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
            self.line('<info>ALL :</info> %s' % self.option('all'))
            self.line('<info>path :</info> %s' % self.option('path'))
            self.line('<info>format :</info> %s' % self.option('format'))
            self.line('<info>dockerfile :</info> %s' % self.option('dockerfile'))
            self.line('<info>filename :</info> %s' % self.option('filename'))
        self.__load_configuration()
        files_list = os.path.abspath(self.option('dockerfile'))
        # Parse Docker Path
        for root, dirs, files in os.walk(files_list):
            for file in files:
                if file.endswith("Dockerfile"):
                    dockerfile_full_path = os.path.join(root, file)
                    self.__process_dockerfile(dockerfile_full_path)

        dia = self.build_graph()
        dia.render()

    def __process_dockerfile(self, dockerfile_full_path):
        """

        :param dockerfile_full_path:
        :type dockerfile_full_path: str

        :return: self
        :rtype: self
        """
        output_file = os.path.splitext(dockerfile_full_path)
        output_file = os.path.join(os.path.dirname(output_file[0]), os.path.basename(output_file[0]))

        docker_image = os.path.basename(os.path.dirname(os.path.dirname(output_file)))
        docker_tag = os.path.basename(os.path.dirname(output_file))

        if Output.VERBOSITY_VERBOSE <= self.output.get_verbosity():
            self.line('<info>-> Processing: </info>%s' % dockerfile_full_path)

        with open(dockerfile_full_path, 'r') as fileInput:
            dockerfile_content = fileInput.read()
            data = ([m.groupdict() for m in self.from_regex.finditer(dockerfile_content)])[0]
            key = "webdevops/%s" % docker_image
            self.containers[key] = data.get('image')
            self.__append_tag(key, data.get('tag'))
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
            if name in group_attr['docker']:
                return self.subgraph[group]
        return default_graph

    def __load_configuration(self):
        stream = open(os.path.dirname(__file__) + "/../../conf/diagram.yml", "r")
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
                      directory=self.option('path'))
        dia = self.__apply_styles(dia, self.conf['diagram']['styles'])
        dia.body.append(r'label = "\n\nWebdevops Images\n at :%s"' % date.today().strftime("%d.%m.%Y"))

        # Create subgraph
        for group, group_attr in self.conf['diagram']['groups'].items():
            self.subgraph[group] = Digraph("cluster_" + group)
            self.subgraph[group].body.append(r'label = "%s"' % group_attr['name'])
            self.subgraph[group] = self.__apply_styles(self.subgraph[group], group_attr['styles'])
        for image, base in self.containers.items():
            graph_image = self.__get_graph(dia, image)
            graph_base = self.__get_graph(dia, base)
            if "webdevops" in base:
                if graph_image == graph_base:
                    graph_image.edge(base, image)
                else:
                    graph_image.node(image)
                    self.edges[image] = base
                if self.option('all'):
                    self.__attach_tag(graph_image, image)
            else:
                graph_image.node(image)

        for name, subgraph in self.subgraph.items():
            dia.subgraph(subgraph)

        for image, base in self.edges.items():
            dia.edge(base, image)
        return dia

    def __attach_tag(self, graph, image):
        for tag in self.tags[image]:
            node_name = "%s-%s" % (image, tag)
            graph.node(node_name, label=tag, fillcolor='#eeeeee', shape='folder')
            graph.edge(image, node_name)
