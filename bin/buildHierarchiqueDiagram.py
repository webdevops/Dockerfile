#!/usr/bin/env/python

from datetime import datetime
import os
import argparse
import re
from graphviz import Digraph

PATH = os.path.dirname(os.path.abspath(__file__))
FROM_REGEX = re.compile(ur'FROM\s+(?P<image>[^\s:]+)(:(?P<tag>.+))?', re.MULTILINE)
CONTAINERS = {}

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

def main(args):
    dockerfilePath = os.path.abspath(args.dockerfile)
    
    u = Digraph('webdevops', filename='webdevops.gv')
    u.body.append('size="10,10"')
    u.body.append(r'label = "\n\nWebdevops Containers\n at :%s"' % get_current_date() )
    u.node_attr.update(color='lightblue2', style='filled', shape='box')     

    # Parse Docker file
    for root, dirs, files in os.walk(dockerfilePath):

        for file in files:
            if file.endswith("Dockerfile"):
                processDockerfile(os.path.join(root, file))

    # Build and render graph
    for image, base in CONTAINERS.items():
        if "webdevops" in base:
            u.edge(base, image)
        else:
            u.node(image)
    print u.source


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-d','--dockerfile' ,help='',type=str)
    args = parser.parse_args()
    main(args)
