import os
import pytest
import testinfra

conftest_path = os.path.dirname(os.path.realpath(__file__))
test_path = os.path.join(conftest_path, 'tests')
test_conf_path = os.path.join(test_path, 'conf')
test_conf_app_path = os.path.join(test_conf_path, 'app')

# Use testinfra to get a handy function to run commands locally
check_output = testinfra.get_backend(
    "local://"
).get_module("Command").check_output


@pytest.fixture
def TestinfraBackend(request):
    # Override the TestinfraBackend fixture,
    # all testinfra fixtures (i.e. modules) depend on it.

    docker_id = check_output(
        "docker run -d -v \"%s:/app:ro\" %s tail -f /dev/null",
        test_conf_app_path,
        request.param
    )

    def teardown():
        check_output("docker rm -f %s", docker_id)

    # Destroy the container at the end of the fixture life
    request.addfinalizer(teardown)

    # Return a dynamic created backend
    return testinfra.get_backend("docker://" + docker_id)
