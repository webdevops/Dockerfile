===========================
webdevops/samson-deployment
===========================

These image extends ``zendesk/samson`` and is a webbased deployment service with Ansistrano_, Capistrano_ and
PHP Deployer_.

The original image is only the webbased deployment system and is extended by:

* Ansible_ with Ansistrano_
* Capistrano_
* PHP Deployer_
* git
* rsync
* docker & docker-compose (as client)
* gulp, grunt, bower
* PHP cli & composer_


Environment variables
---------------------




Docker image layout
-------------------



.. _Ansible: https://www.ansible.com/
.. _Ansistrano: http://capistranorb.com
.. _Capistrano: https://github.com/ansistrano/deploy
.. _Deployer: http://deployer.org/
.. _composer: https://getcomposer.org/
