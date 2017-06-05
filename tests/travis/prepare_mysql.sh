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

echo "Preparing for MySQL ..."

if [[ "$MYSQL_VERSION" == "5.7" ]]; then
	echo "Using MySQL 5.7 in Docker container, need to use TCP"
	export PROTO="--protocol=TCP"
fi

# Print out some MySQL information
mysql --version
mysql $PROTO -e "SELECT VERSION();"
mysql $PROTO -e "SHOW VARIABLES LIKE 'max_allowed_packet';"
mysql $PROTO -e "SHOW VARIABLES LIKE 'collation_%';"
mysql $PROTO -e "SHOW VARIABLES LIKE 'character_set%';"
mysql $PROTO -e "SELECT @@sql_mode;"

# Create Hubzilla database
mysql $PROTO -u root -e "CREATE DATABASE IF NOT EXISTS hubzilla;";
mysql $PROTO -u root -e "CREATE USER 'hubzilla'@'localhost' IDENTIFIED BY 'hubzilla';"
mysql $PROTO -u root -e "GRANT ALL ON hubzilla.* TO 'hubzilla'@'localhost';"

# Import table structure
mysql $PROTO -u root hubzilla < ./install/schema_mysql.sql

# Show databases and tables
mysql $PROTO -u root -e "SHOW DATABASES;"
mysql $PROTO -u root -e "USE hubzilla; SHOW TABLES;"
