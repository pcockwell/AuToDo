#!/bin/sh
MYSQL_USER=root

echo "DROP DATABASE IF EXISTS autodo;" | mysql -u $MYSQL_USER
echo "CREATE DATABASE autodo;" | mysql -u $MYSQL_USER

mysql -u $MYSQL_USER autodo < autodo_schema.sql
