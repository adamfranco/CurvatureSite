CurvatureSite
=============

This program generates a set of static `index.html` files and associated resources
to improve browsing of a directory-tree of Curvature `.kml`/`.kmz` files.

The HTML files will be written to the target directory to be intermingled with the
`.kml`/`.kmz` files. Afterward, the target directory will be suitable for serving
from any web host or mirror without need for server-side processing.

Installation
------------
You must install dependencies by running `composer install` in this directory.

If you do not have composer installed on your machine, you can get it as described
here: https://getcomposer.org/

1. Download the composer.phar to the current directory with:
     curl -sS https://getcomposer.org/installer | php
2. Install dependencies of this package:
     php composer.phar install
