# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [WebDevOps.io Dockerfile](https://github.com/webdevops/Dockerfile).

## [0.21.0] - 2016-03-20
### Changed
- Improved entrypoint startup time
- Removed entrypoint ansible provisioning if not needed
- Added java-jre for samson-deployment

## [0.20.0] - 2016-02-24
### Added
- Added sqlite to base images

### Changed
- Moved WEB_DOCUMENT_ROOT to /app (from /application/code) 
- Improved samson-deployment
