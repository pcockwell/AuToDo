#!/bin/sh
MYSQL_USER=root

echo "DROP DATABASE IF EXISTS autodo;" | mysql -u $MYSQL_USER -p
echo "CREATE DATABASE autodo;" | mysql -u $MYSQL_USER -p

mysql -u $MYSQL_USER -p autodo < autodo_schema.sql
