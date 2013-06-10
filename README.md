AuToDo
======

Setup instructions
------------------

1. Setup your Apache, PHP5, and MySQL server.
2. Ensure you have installed and enabled the PHP curl, mysql, mcrypt and mbstring extensions
3. Enable mod_rewrite in your apache httpd.conf
	- Uncomment the LoadModule line for mod_rewrite. If this line is not present, run 'sudo a2enmod rewrite'
	- Find the line that says 'DocumentRoot /path/to/doc/root/', and underneath you will find a <Directory> definition
	- In the <Directory> definition set the 'Options' and 'AllowOverride' variables to 'All'
4. Point webserver to laravel/public/ directory
	- Edit your 'hosts' file add `127.0.0.1    autodo`
	- Edit your virtual hosts file (httpd.conf will do fine if you don't have a dedicated vhosts file) and add the following lines

```
<VirtualHost *:80>
    DocumentRoot "/path/to/webserver/autodo/laravel/public"
    ServerName autodo
</VirtualHost>
```

5. Install [composer](http://www.getcomposer.org)
6. Go to the laravel directory and run 'composer install'
7. Go to http://autodo/
