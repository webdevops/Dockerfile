# PHP tools container for static analyse
Container                                 | Distribution name        | PHP Version                                                               
----------------------------------------- | -------------------------|---------------
`webdevops/php-audit:debian-7`            | wheezy                   | PHP 5.4
`webdevops/php-audit:debian-8`            | jessie                   | PHP 5.6
`webdevops/php-audit:debian-8-php7`       | jessie with dotdeb       | PHP 7.x (via dotdeb)
`webdevops/php-audit:debian-9`            |                          | PHP 5.x
`webdevops/php-audit:debian-9-php7`       |                          | PHP 7.x
`webdevops/php-audit:centos-7`            |                          | PHP 5.4
`webdevops/php-audit:centos-7-php56`      |                          | PHP 5.6 (via webtatic)
`webdevops/php-audit:centos-7-php7`       |                          | PHP 7.0 (via webtatic)
`webdevops/php-audit:alpine-3`            |                          | PHP 5.6 

## Available tools

- [PHPLoc](https://github.com/sebastianbergmann/phploc) 
- [PHP Depend](https://pdepend.org/) 
- [PHPUnit](https://phpunit.de/) 
- [PHP Mess Detector](https://phpmd.org/) 
- [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) 
- [PHP Copy/Paste Detector](https://github.com/sebastianbergmann/phpcpd) 
- [PHP Dead Code Detector](https://github.com/sebastianbergmann/phpdcd) 
- [PHP Coding Standards Fixer](http://cs.sensiolabs.org/) 
- [SensioLabs DeprecationDetector](https://github.com/sensiolabs-de/deprecation-detector)
- [PHP 7 Compatibility Checker](https://github.com/sstalle/php7cc)

## Environment variables

Variable              | Description
--------------------- |  ------------------------------------------------------------------------------
`PHP_DEBUGGER`        | Either `xdebug`, `blackfire` or `none`. Default is `xdebug`.
