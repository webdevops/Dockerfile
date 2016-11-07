import testinfra
import pytest
import re
from webdevops import DockerfileUtility

class TestinfraDockerPlugin:
    docker_image_list = []
    configuration = False

    def __init__(self, configuration):
        """
        Constructor
        """
        self.configuration = configuration
        self.init_docker_image_list()

    def init_docker_image_list(self):
        """
        Init and build list of available docker images
        """
        dockerfile_list = DockerfileUtility.find_dockerfiles_in_path(
            base_path=self.configuration['basePath'],
            path_regex=self.configuration['docker']['pathRegex'],
            image_prefix=self.configuration['docker']['imagePrefix'],
            whitelist=self.configuration['whitelist'],
            blacklist=self.configuration['blacklist'],
        )

        for image in dockerfile_list:
            self.docker_image_list.append(image['image']['fullname'])

    def get_image_list_by_regexp(self, filter_regexp):
        """
        Get image list by filtering via filter regexp
        """
        ret = []

        filter_regexp = re.compile(filter_regexp, re.IGNORECASE)

        for image_name in self.docker_image_list:
            if filter_regexp.search(image_name):
                ret.append(image_name)

        return ret

    def pytest_generate_tests(self, metafunc):
        """
        Generate tests
        """
        if "TestinfraBackend" in metafunc.fixturenames:
            images = []

            # Lookup "docker_images" marker
            marker = getattr(metafunc.function, "docker_images", None)
            if marker is not None:
                for marker_image_name in marker.args:
                    images.extend(self.get_image_list_by_regexp(marker_image_name))
            print images

            # If the test has a destructive marker, we scope TestinfraBackend
            # at function level (i.e. executing for each test). If not we scope
            # at session level (i.e. all tests will share the same container)
            if getattr(metafunc.function, "destructive", None) is not None:
                scope = "function"
            else:
                scope = "session"

            metafunc.parametrize(
                "TestinfraBackend", images, indirect=True, scope=scope)
