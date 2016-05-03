[<img src="https://travis-ci.org/tinify/wordpress-plugin.svg?branch=master" alt="Build Status">](https://travis-ci.org/tinify/wordpress-plugin)

# Compress JPEG & PNG images for WordPress

Make your website faster by optimizing your JPEG and PNG images.

This plugin automatically optimizes your images by integrating with the
popular image compression services TinyJPG and TinyPNG. You can download the
plugin from https://wordpress.org/plugins/tiny-compress-images/.

Learn more about TinyJPG and TinyPNG at https://tinypng.com/.

## Contact us

Got questions or feedback? Let us know! Contact us at support@tinypng.com.

## Information for plugin contributors

### Prerequisites
* A working Docker installation (https://docs.docker.com/installation/).
* Composer (https://getcomposer.org/download/).
* PhantomJS 2.1 or greater (http://phantomjs.org).
* MySQL client and admin tools.

### Running the plugin in WordPress
1. Run `bin/run-wordpress <version>`. E.g. `bin/run-wordpress 41`.
2. Use `docker ps` to check which port to use to connect to WordPress.

### Running the unit tests
1. Run `bin/unit-tests`.

### Running the integration tests
1. Start PhantomJS server: `phantomjs --wd`. Tested with version 2.1.1.
2. Run `bin/integration-tests $version [$to_version]` (When $to_version is
added, all versions between $version and $to_version are tested). E.g.
`bin/integration-tests 41` or `bin/integration-tests 40 42`.

### Translating the plugin
Language packs will be generated for the plugin once translations for a
language are 100% filled in and approved.

See https://translate.wordpress.org/projects/wp-plugins/tiny-compress-images.

## License

Copyright (C) 2015-2016 Voormedia B.V.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

[View the complete license](LICENSE).
