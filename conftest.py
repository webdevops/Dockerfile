import os, time
import pytest, testinfra

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

    docker_command = ''
    docker_image = request.param
    docker_sleeptime = 5

    # Check for custom command in docker image name
    if "#" in docker_image:
        docker_image, docker_command = docker_image.split("#", 2)

        if docker_command == "loop":
            docker_command = 'tail -f /dev/null'
            docker_sleeptime = 0

    docker_id = check_output(
        "docker run -d -v \"%s:/app:ro\" %s " + docker_command,
        test_conf_app_path,
        docker_image
    )

    def teardown():
        check_output("docker rm -f %s", docker_id)

    # Destroy the container at the end of the fixture life
    request.addfinalizer(teardown)

    # wait for getting the image up
    if docker_sleeptime:
        time.sleep(docker_sleeptime)

    # Return a dynamic created backend
    return testinfra.get_backend("docker://" + docker_id)
