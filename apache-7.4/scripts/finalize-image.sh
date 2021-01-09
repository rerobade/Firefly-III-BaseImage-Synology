#!/bin/bash

echo "Now in finalize-image.sh for Firefly III"
echo "Script version is 1.0.3 (2020-12-16)"
echo "Running as '$(whoami)'."

echo "Making directories..."
mkdir -p $FIREFLY_III_PATH/storage/app/public
mkdir -p $FIREFLY_III_PATH/storage/build
mkdir -p $FIREFLY_III_PATH/storage/database
mkdir -p $FIREFLY_III_PATH/storage/debugbar
mkdir -p $FIREFLY_III_PATH/storage/export
mkdir -p $FIREFLY_III_PATH/storage/framework/cache/data
mkdir -p $FIREFLY_III_PATH/storage/framework/sessions
mkdir -p $FIREFLY_III_PATH/storage/framework/testing
mkdir -p $FIREFLY_III_PATH/storage/framework/views/twig
mkdir -p $FIREFLY_III_PATH/storage/framework/views/v1
mkdir -p $FIREFLY_III_PATH/storage/framework/views/v2
mkdir -p $FIREFLY_III_PATH/storage/logs
mkdir -p $FIREFLY_III_PATH/storage/upload

echo "Done making directories."