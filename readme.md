# Offical Firefly III Docker base image

Firefly III and the associated tools use the same basic image. This image is built on the php/7.4-apache image with some minor changes and additions. This repository contains the Docker files for the image that Firefly III uses as a base.

## Changes

Basically, I use the 7.4 Apache image with some minor changes.

- Switch to port 8080
- Add extensions for Firefly III
- Install localizations
- Add custom start & run scripts

## Usage
	
## Some interesting links

- [Firefly III on GitHub](https://github.com/firefly-iii/firefly-iii)
- [The documentation for Firefly III](https://docs.firefly-iii.org/)
* [jc5x/firefly-iii-base-image:latest](https://hub.docker.com/repository/docker/jc5x/firefly-iii-base-image)
* [fireflyiii/tools-base-image:latest](https://hub.docker.com/repository/docker/fireflyiii/tools-base-image)

## Issues and questions

Please open issues on GitHub. Thank you.