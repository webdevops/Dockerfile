import pytest

@pytest.mark.docker_images('webdevops/php:')
@pytest.mark.docker_images('webdevops/hhvm:')
@pytest.mark.flaky(reruns=10)
def test_php_fpm_socket(Socket):
    assert Socket("tcp://0.0.0.0:9000").is_listening

@pytest.mark.docker_images('webdevops/php-apache')
@pytest.mark.docker_images('webdevops/php-nginx')
@pytest.mark.docker_images('webdevops/hhvm-apache')
@pytest.mark.docker_images('webdevops/hhvm-nginx')
@pytest.mark.flaky(reruns=10)
def test_php_fpm_socket(Socket):
    assert Socket("tcp://127.0.0.1:9000").is_listening
