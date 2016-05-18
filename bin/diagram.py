#!/usr/bin/env/python

from datetime import datetime
import os
import argparse
import re
from graphviz import Digraph
import yaml

PATH = os.path.dirname(os.path.abspath(__file__))
FROM_REGEX = re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>.+))?', re.MULTILINE)
CONTAINERS = {}
SUBGRAPH = {}
EDGES = {}

def get_current_date():
    import datetime
    return datetime.date.today().strftime("%d.%m.%Y")

def processDockerfile(inputFile):
    outputFile = os.path.splitext(inputFile)
    outputFile = os.path.join(os.path.dirname(outputFile[0]),os.path.basename(outputFile[0]))

    dockerImage = os.path.basename(os.path.dirname(os.path.dirname(outputFile)))
    dockerTag = os.path.basename(os.path.dirname(outputFile))

    with open(inputFile, 'r') as fileInput:
        DockerfileContent = fileInput.read()
        data = ([m.groupdict() for m in FROM_REGEX.finditer(DockerfileContent)])[0]
        CONTAINERS["webdevops/%s"%dockerImage] = data.get('image')

def apply_styles(graph, styles):
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

def get_graph(conf,default_graph,name):

    for group, group_attr in conf['diagram']['groups'].items():
        if name in group_attr['docker']:
            return SUBGRAPH[group]
    return default_graph

def build_graph():
    stream = open(os.path.dirname(__file__)+"/diagram.yml", "r")
    conf_diagram = yaml.safe_load(stream)
    dia = Digraph('webdevops', filename='webdevops.gv')
    dia = apply_styles(dia,conf_diagram['diagram']['styles'])
    dia.body.append(r'label = "\n\nWebdevops Containers\n at :%s"' % get_current_date() )

    # Create subgraph
    for group, group_attr in conf_diagram['diagram']['groups'].items():
        SUBGRAPH[group] = Digraph("cluster_"+group);
        SUBGRAPH[group].body.append(r'label = "%s"' % group_attr['name'] )
        SUBGRAPH[group] = apply_styles(SUBGRAPH[group],group_attr['styles'] )
    for image, base in CONTAINERS.items():
        graph_image = get_graph(conf_diagram, dia, image)
        graph_base = get_graph(conf_diagram, dia, base)
        if "webdevops" in base:
            if graph_image == graph_base:
                graph_image.edge(base, image)
            else:
                graph_image.node(image)
                EDGES[image] = base
        else:
            graph_image.node(image)


    for name, subgraph in SUBGRAPH.items():
        dia.subgraph(subgraph)

    for image, base in EDGES.items():
        dia.edge(base, image)
    print dia.source


def main(args):
    dockerfilePath = os.path.abspath(args.dockerfile)

    # Parse Docker file
    for root, dirs, files in os.walk(dockerfilePath):

        for file in files:
            if file.endswith("Dockerfile"):
                processDockerfile(os.path.join(root, file))

    build_graph()


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-d','--dockerfile' ,help='',type=str)
    args = parser.parse_args()
    main(args)
