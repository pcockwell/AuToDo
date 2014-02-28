#!/bin/bash

SCRIPT=$(readlink -f "$0")
SCRIPTPATH=$(dirname "$SCRIPT")
CONFIGPATH=${SCRIPTPATH}/../phpunit.xml

${SCRIPTPATH}/../vendor/bin/phpunit --verbose -c ${CONFIGPATH}

