import pytest
import stat
import re

@pytest.mark.docker_loop()
@pytest.mark.docker_images('webdevops/liquibase')
def test_liquibase_cmd(Command, File):
    assert File('/usr/local/bin/liquibase').exists
    assert File('/usr/local/bin/liquibase').mode == 0o777

    cmd = Command("liquibase --version")
    assert cmd.rc == 0
    assert 'Liquibase Version:' in cmd.stderr

