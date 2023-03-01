#!/bin/sh
rm -rf ./build
php build.php ./example ./build
ls -R ./build
