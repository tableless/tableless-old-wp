# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.4.0] - 2016-11-11
### Added
- Add a `get_extension_url` method to `Yoast_Product` to retrieve the URL where people can extend/upgrade their license.
- Add a `set_extension_url` method to `Yoast_Product` to set the URL where people can extend/upgrade their license.

### Changed
- Removed development files from zip that GitHub generates by settings export-ignore for certain files in `.gitattributes`, props [Danny van Kooten](https://github.com/dannyvankooten).

### Fixed
- Add missing gettext functions to several strings, props [Pedro Mendonça](https://github.com/pedro-mendonca)
- Improve text string for the new version notification, props [Xavi Ivars](https://github.com/xavivars)
- Fix alignment of license fields by setting WordPress classes that have certain default styles that align form elements correctly.

## [1.3.0] - 2016-06-14
### Fixed
- Fix issue where the transient would be overwritten for different products with different slugs.
