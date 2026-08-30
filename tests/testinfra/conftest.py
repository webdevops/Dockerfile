import pytest
import sys
import os

# Add the bin directory to the Python path
sys.path.insert(0, os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), 'bin'))
from webdevops.testinfra import TestinfraDockerPlugin

def pytest_configure(config):
    """
    Register custom pytest marks
    """
    config.addinivalue_line(
        "markers", "docker_images: mark test to run against specific docker images"
    )
    config.addinivalue_line(
        "markers", "docker_loop: mark test to run in loop mode"
    )
    config.addinivalue_line(
        "markers", "docker_images_blacklist: mark test to exclude specific docker images"
    )
    config.addinivalue_line(
        "markers", "destructive: mark test as destructive (requires function scope)"
    )

def pytest_generate_tests(metafunc):
    """
    Generate tests using TestinfraDockerPlugin
    """
    if "testinfra_backend" in metafunc.fixturenames:
        # This will be handled by the TestinfraDockerPlugin when running through the console
        # For direct pytest runs, we need to handle this differently
        pass
