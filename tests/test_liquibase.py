import pytest
import stat
import re

@pytest.fixture()
def Liquibase(Command):
    def f(arg):
        return Command.check_output("liquibase %s", arg)
    return f

@pytest.mark.docker_images('webdevops/liquibase')
def test_liquibase_cmd(Command, File):
    assert File('/usr/local/bin/liquibase').exists
    assert File('/usr/local/bin/liquibase').mode == 0o777

    cmd = Command("liquibase --version")
    assert cmd.rc == 0
    assert 'Liquibase Version:' in cmd.stderr

