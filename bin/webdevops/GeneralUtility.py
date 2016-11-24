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

def cmd_execute(cmd, cwd=False, env=None):
    """
    Execute cmd and output stdout/stderr
    """

    print 'Execute: %s' % ' '.join(cmd)

    if cwd:
        path_current = os.getcwd()
        os.chdir(cwd)

    file_stdout = tempfile.NamedTemporaryFile()

    proc = subprocess.Popen(
        cmd,
        stdout=file_stdout,
        stderr=file_stdout,
        bufsize=-1,
        env=env
    )

    while proc.poll() is None:
        pass

    with open(file_stdout.name, 'r') as f:
        for line in f:
            print line.rstrip('\n')

    if cwd:
        os.chdir(path_current)

    if proc.returncode == 0:
        return True
    else:
        print '>> failed command with return code %s' % proc.returncode
        return False
