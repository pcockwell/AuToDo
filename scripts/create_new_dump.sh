#!/bin/sh
MYSQL_USER=root

mysqldump --add-drop-table -u $MYSQL_USER -p autodo > autodo_data.sql
