==================
webdevops/base-app
==================

The ``base-app`` image extends the ``base`` image with additional tools and all locales.

Packages:

- OpenSSH server (disabled by default) and client
- MySQL client
- sqlite
- dnsmasq (disabled by default)
- postfix (disabled by default)
- sudo
- zip, unzip, bzip2
- wget, curl
- net-tools
- moreutils
- dns utils
- rsync
- git
- nano, vim

Because some applications are using locales for translations (eg. date formatting) all locales are generated inside
this image.

For an example docker service

.. attention:: Alpine doesn't provide any locales so you have to find another method for using locales!
