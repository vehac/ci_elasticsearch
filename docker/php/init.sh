#!/bin/bash

echo "------------------ Permissions folder ---------------------"
bash -c 'chmod -R 777 /var/www/html/application/cache'

echo "------------------ Starting apache server ------------------"
exec "apache2-foreground"