import pytest

@pytest.mark.docker_images('webdevops/php:')
@pytest.mark.flaky(reruns=10)
def test_php_fpm_socket(Socket):
    assert Socket("tcp://0.0.0.0:9000").is_listening
