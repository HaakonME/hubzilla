#!/usr/bin/env bash

#
# Copyright (c) 2016 Hubzilla
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.
#

# Exit if anything fails
set -e

# Only create and deploy API documentation once, on first build job.
# Waiting for upcoming 'Build Stages' Q1/Q2 2017 to make this cleaner.
# https://github.com/travis-ci/travis-ci/issues/929
if [[ "$TRAVIS_JOB_NUMBER" != "${TRAVIS_BUILD_NUMBER}.1" ]]; then
	echo "Not the first build job. Creating API documentation only once is enough."
	echo "We are finished ..."
	exit
fi

echo "Doxygen version >= 1.8 is required"
doxygen --version

# Check if newer version of Doxygen should be used
if [ ! -z "$DOXY_VER" ]; then
	export DOXY_BINPATH=$HOME/doxygen/doxygen-$DOXY_VER/bin
	if [ ! -e "$DOXY_BINPATH/doxygen" ]; then
		echo "Installing newer Doxygen $DOXY_VER ..."
		mkdir -p $HOME/doxygen && cd $HOME/doxygen
		wget -O - http://ftp.stack.nl/pub/users/dimitri/doxygen-$DOXY_VER.linux.bin.tar.gz | tar xz
		export PATH=$DOXY_BINPATH:$PATH
	fi
	echo "Doxygen version"
	doxygen --version
fi

echo "Generating Doxygen API documentation ..."
cd $TRAVIS_BUILD_DIR
mkdir -p ./doc/html
# Redirect stderr and stdout to log file and console to be able to review documentation errors
doxygen $DOXYFILE 2>&1 | tee ./doc/html/doxygen.log

# Check if Doxygen successfully created the documentation
if [ -d "doc/html" ] && [ -f "doc/html/index.html" ]; then
	echo "API documentation generated"
	if [ -n "${TRAVIS_TAG}" ]; then
		echo "Generate API documentation archive for release deployment ..."
		zip -9 -r -q doc/hubzilla-api-documentation.zip doc/html/
	fi
else
	echo "No API documentation files have been found" >&2
	exit 1
fi
