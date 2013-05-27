AuToDo
======

Setup instructions
------------------

1. Setup your Apache, PHP5, and MySQL server.
2. Ensure you have installed and enabled the PHP curl, mysql, and mbstring extensions
3. Enable mod_rewrite in your apache httpd.conf
	- Uncomment the LoadModule line for mod_rewrite
	- Find the line that says 'DocumentRoot /path/to/doc/root/', and underneath you will find a <Directory> definition
	- In the <Directory> definition set the 'Options' and 'AllowOverride' variables to 'All'
4. Point webserver to laravel/public/ directory
