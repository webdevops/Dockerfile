import pytest
import stat
import re

@pytest.mark.docker_loop()
@pytest.mark.docker_images('webdevops/liquibase')
def test_liquibase_cmd(testinfra_backend):
    command = testinfra_backend.run
    file = testinfra_backend.file
    assert file('/usr/local/bin/liquibase').exists
    assert file('/usr/local/bin/liquibase').mode == 0o777

    cmd = command("liquibase --version")
    assert cmd.rc == 0
    assert 'Liquibase Version:' in cmd.stderr

