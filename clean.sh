#!/usr/bin/env bash

set -e

if command -v gfind 2>&1 >/dev/null
then
    FIND_BINARY='gfind'
else
    FIND_BINARY='find'
fi

$FIND_BINARY -maxdepth 2 -type d -path './public/*' -not -path './public/assets' > files.txt
$FIND_BINARY -maxdepth 2 -type f -path './public/*.html' >> files.txt
$FIND_BINARY -maxdepth 2 -type f -path './public/*.xml' >> files.txt
$FIND_BINARY -maxdepth 2 -type f -path './cache/*.php' >> files.txt

cat files.txt
cat files.txt | xargs rm -rf
rm files.txt
