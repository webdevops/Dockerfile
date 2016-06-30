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
TAGS = {}
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
        key="webdevops/%s"%dockerImage
        CONTAINERS[key] = data.get('image')
        appendTag(key, data.get('tag'))

def appendTag(dockerImage, tag):
    if False == TAGS.has_key(dockerImage):
        TAGS[dockerImage] = {}
    TAGS[dockerImage][tag] = tag

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

def build_graph(args):
    stream = open(os.path.dirname(__file__)+"/diagram.yml", "r")
    conf_diagram = yaml.safe_load(stream)
    dia = Digraph('webdevops', filename=args.filename, format=args.format, directory=args.path)
    dia = apply_styles(dia,conf_diagram['diagram']['styles'])
    dia.body.append(r'label = "\n\nWebdevops Images\n at :%s"' % get_current_date() )

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
            if args.all :
                attach_tag(graph_image, image)
        else:
            graph_image.node(image)

    for name, subgraph in SUBGRAPH.items():
        dia.subgraph(subgraph)

    for image, base in EDGES.items():
        dia.edge(base, image)
    return dia

def attach_tag(graph, image):
    for tag in TAGS[image]:
        node_name = "%s-%s"%(image,tag)
        node = graph.node(node_name, label=tag, fillcolor='#eeeeee', shape='folder' )
        edge = graph.edge(image, node_name )

def main(args):
    dockerfilePath = os.path.abspath(args.dockerfile)

    # Parse Docker file
    for root, dirs, files in os.walk(dockerfilePath):

        for file in files:
            if file.endswith("Dockerfile"):
                processDockerfile(os.path.join(root, file))

    dia = build_graph(args)
    dia.render()
    print " render to: %s"%args.filename


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-d','--dockerfile' ,help='path to the folder containing dockerfile analyze',type=str)
    parser.add_argument('-f','--filename' ,help='file output (default: webdevops.gv)',default='webdevops.gv',type=str)
    parser.add_argument('-F','--format' ,help='output format (default: png)',default='png',choices=('png','jpg','pdf','svg'))
    parser.add_argument('-p','--path' ,help='path output',default=os.path.dirname(__file__)+"/../",type=str)
    parser.add_argument('--all' ,help='show all info',dest='all' ,action='store_true')
    parser.set_defaults(all=False)

    args = parser.parse_args()

    main(args)
