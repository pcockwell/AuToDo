#!/bin/sh
MYSQL_USER=root

echo "DROP DATABASE IF EXISTS autodo;" | mysql -u $MYSQL_USER -p$1
echo "CREATE DATABASE autodo;" | mysql -u $MYSQL_USER -p$1

mysql -u $MYSQL_USER -p$1 autodo < autodo_schema.sql
