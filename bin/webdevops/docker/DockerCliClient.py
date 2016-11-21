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

import subprocess
import os
import tempfile
from .DockerBaseClient import DockerBaseClient


class DockerCliClient(DockerBaseClient):

    def pull_image(self, name, tag):
        """
        Build dockerfile
        """
        cmd = ['docker', 'pull', '%s:%s' % (name, tag)]
        return self.cmd_execute(cmd)

    def build_dockerfile(self, path, name, nocache=False):
        """
        Build dockerfile
        """
        cmd = ['docker', 'build', os.path.dirname(path), '--tag', name]

        if nocache:
            cmd.append('--no-cache')

        return self.cmd_execute(cmd)

    def push_image(self, name):
        """
        Push one Docker image to registry
        """
        cmd = ['docker', 'push', name]
        return self.cmd_execute(cmd)

    def cmd_execute(self, cmd):
        """
        Execute cmd and output stdout/stderr
        """

        print 'Run Docker CLI: %s' % ' '.join(cmd)

        file_stdout = tempfile.NamedTemporaryFile()

        proc = subprocess.Popen(
            cmd,
            stdout=file_stdout,
            stderr=file_stdout,
            bufsize=-1,
        )

        while proc.poll() is None:
            pass

        with open(file_stdout.name, 'r') as f:
            for line in f:
                print line.rstrip('\n')

        if proc.returncode == 0:
            return True
        else:
            print '>> failed command with return code %s' % proc.returncode
            return False
