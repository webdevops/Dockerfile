#!/usr/bin/env/python

from jinja2 import Environment, FileSystemLoader
from datetime import datetime
import os
import argparse

PATH = os.path.dirname(os.path.abspath(__file__))

templateHeader = '{% extends "Dockerfile/layout.jinja2" %}\n{% block content %}'
templateFooter = '{% endblock %}'

def get_current_date():
    import datetime
    return datetime.date.today().strftime("%d.%m.%Y")

def processDockerfile(inputFile, template):
    outputFile = os.path.splitext(inputFile)
    outputFile = os.path.join(os.path.dirname(outputFile[0]),os.path.basename(outputFile[0]))

    dockerImage = os.path.basename(os.path.dirname(os.path.dirname(outputFile)))
    dockerTag = os.path.basename(os.path.dirname(outputFile))

    context = {
        'Dockerfile': {
            'image': dockerImage,
            'tag': dockerTag
        }
    }

    print "* Processing Dockerfile for " + dockerImage + ":" + dockerTag

    with open(inputFile, 'r') as fileInput:
        templateContent = fileInput.read()

        templateContent = templateHeader + templateContent + templateFooter

        renderedContent = template.from_string(templateContent).render(context)
        renderedContent = renderedContent.lstrip()

        with open(outputFile, 'w') as fileOutput:
            fileOutput.write(renderedContent)



def main(args):
    templatePath = os.path.abspath(args.template)
    dockerfilePath = os.path.abspath(args.dockerfile)

    template = Environment(
        autoescape=False,
        loader=FileSystemLoader([templatePath]),
        trim_blocks=False
    )

    for root, dirs, files in os.walk(dockerfilePath):
        for file in files:
            if file.endswith("Dockerfile.jinja2"):
                processDockerfile(os.path.join(root, file), template)


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-t','--template' ,help='',type=str)
    parser.add_argument('-d','--dockerfile' ,help='',type=str)
    args = parser.parse_args()
    main(args)
