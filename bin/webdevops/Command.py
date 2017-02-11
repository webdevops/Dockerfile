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

import os, subprocess, tempfile, copy, time

def execute(cmd, cwd=False, env=None):
    """
    Execute cmd and output stdout/stderr
    """

    print 'Execute: %s' % ' '.join(cmd)

    if env is not None:
        env = copy.deepcopy(env)
        env['PWD'] = os.environ['PWD']
        env['LC_CTYPE'] = os.environ['LC_CTYPE']
        env['SHELL'] = os.environ['SHELL']

        if '_' in env:
            del env['_']

        # add system env vars
        system_env_vars = ['PWD', 'PATH', 'LC_CTYPE', 'SHELL', 'DOCKER_HOST', 'DOCKER_CERT_PATH', 'DOCKER_MACHINE_NAME', 'DOCKER_TLS_VERIFY']
        for var_name in system_env_vars:
            if not var_name in env and var_name in os.environ:
                env[var_name] = os.environ[var_name]

    # set current working directory
    path_current = os.getcwd()
    if cwd:
        os.chdir(cwd)

    # stdout file
    # (stdout and stderr will be redirected to it, pieping both isn't possible)
    file_stdout = tempfile.NamedTemporaryFile()

    # create subprocess
    proc = subprocess.Popen(
        cmd,
        stdout=file_stdout,
        stderr=file_stdout,
        bufsize=-1,
        env=env
    )

    # wait for process end
    while proc.poll() is None:
        time.sleep(1)

    # output stdout
    with open(file_stdout.name, 'r') as f:
        for line in f:
            print line.rstrip('\n')

    # restore current work directory
    os.chdir(path_current)

    if proc.returncode == 0:
        return True
    else:
        print '>> failed command with return code %s' % proc.returncode
        return False
