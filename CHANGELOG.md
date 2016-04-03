# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [WebDevOps.io Dockerfile](https://github.com/webdevops/Dockerfile).

## [0.22.0] - upcoming
### Changed
- Introduced base-app for application images
- Added alpine-3 images

## [0.21.6] - 2016-03-25
### Changed
- Refactored directory layout (container -> docker/, .bin -> bin, testsuite -> test/

## [0.21.5] - 2016-03-24
### Added
- Fixed permissions automatically for /tmp if mounted as volume
- Added error checks for samson service script

## [0.21.0] - 2016-03-20
### Changed
- Improved entrypoint startup time
- Removed entrypoint ansible provisioning if not needed
- Added java-jre and latest npm for samson-deployment


## [0.20.0] - 2016-02-24
### Added
- Added sqlite to base images

### Changed
- Moved WEB_DOCUMENT_ROOT to /app (from /application/code) 
- Improved samson-deployment
