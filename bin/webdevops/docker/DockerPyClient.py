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
from .DockerBaseClient import DockerBaseClient

class DockerPyClient(DockerBaseClient):

    docker_client = False
    last_output_line = False

    def __init__(self, configuration):
        """
        Constrcutor
        """
        DockerBaseClient.__init__(self)

        import docker

        # Init docker client
        self.docker_client = docker.from_env(assert_hostname=False)

    def pull_image(self, name, tag):
        """
        Build dockerfile
        """
        response = self.docker_client.pull(
            repository=name,
            tag=tag,
            stream=True,
            decode=True
        )

        return self.process_client_response(response)

    def build_dockerfile(self, path, name, nocache=False):
        """
        Build dockerfile
        """
        response = docker_client.build(
            path=os.path.dirname(path),
            tag=name,
            pull=False,
            nocache=nocache,
            quiet=False,
            decode=True
        )

        return self.process_client_response(response)

    def push_image(self, name):
        """
        Push one Docker image to registry
        """
        response = docker_client.push(
            name,
            stream=True,
            decode=True
        )
        return self.process_client_response(response)

    def process_client_response(self, response):
        ret = True
        last_message = False

        def output_message(message, prevent_repeat=False):
            # Prevent repeating messages
            if prevent_repeat:
                if self.last_output_line and self.last_output_line == message:
                    return
                self.last_output_line = message
            else:
                self.last_output_line = False

            sys.stdout.write(message.strip(' \t\n\r') + '\n')

        if not response:
            return False

        for line in response:
            # Keys
            #   - error
            #   - stream
            #   - status, progressDetail, id
            #   - progressDetail | aux [ tag, digest, size ]
            if 'error' in line:
                output_message(line['error'])
                ret = False
            if 'stream' in line:
                output_message(line['stream'], prevent_repeat=True)
            if 'status' in line:
                message = line['status']
                if 'id' in line:
                    message += ' ' + line['id']
                output_message(message)
        print ''
        return ret
