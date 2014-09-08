## Server Requirements

To install and run Membrr and the OpenGateway billing server, you will need:

*   A web server (shared hosting, virtual dedicated, or dedicated servers are OK)
*   PHP5 support on your web server
*   cURL support in your PHP installation
*   IonCube Loader support for your PHP installation
*   A MySQL database
*   mod_rewrite (or an equivalent module) enabled for your web server
*   crontab access
*   An ExpressionEngine license and installation (either EE1.6.8+ or EE2.1+).

The OpenGateway control panel must be accessed using the latest Chrome, Safari, Opera, FireFox, or IE8 browser.

It is also highly recommended to have an SSL certificate installed as you will be dealing with transmitting credit card data. Credit card data is not stored but it's still import to have an SSL certificate setup so that the data is not intercepted during transmission.

### Web Server

Most web server types are okay, including Apache 1/2, lighttpd, IIS, etc. A dedicated server is recommended for increased security but the application will run in any hosting environment, shared or otherwise.

It is required that you have mod_rewrite or an equivalent module. This module will interpret the `.htaccess` in the root folder of your OpenGateway installation and rewrite links like `/transactions/create` to `index.php?c=transactions&m=create`, thereby making the URL's cleaner. This is a 100% requirement as we create URL's such as `/api` assuming mod_rewrite is enabled.

During the OpenGateway install wizard, you will be instructed to create 2 cronjobs. You will need to use a crontab manager either in your cPanel/Plesk web hosting manager or via SSH access to your server. Full crontab instructions are given during install.

### PHP

PHP5 is required because the OpenGateway software relies on some functions that are only in PHP5. PHP4 users will experience problems in certain areas of the server.  If this is OK with you, the Membrr ExpressionEngine
plugin will operate in a PHP4 environment so this isn't a 100% requirement but rather a strong recommendation.

Your PHP setup should be configured with:

*   XML support with SimpleXML (installed by default as of PHP 5.13)
*   enable_short_tags = On
*   safe_mode = Off
*   cURL support with SSL support
*   GD2 image extension support
*   The free IonCube loaders installed and configured.  Parts of the OpenGateway library are encoded.  The Membrr module is
    open source and editable.  [Click here to download the free IonCube loaders.](http://www.ioncube.com/loaders.php)

### MySQL

For the time being, we require a MySQL database server. You must have a MySQL user account with complete READ/WRITE access to an empty MySQL database.

### ExpressionEngine

Membrr will install into either the ExpressionEngine 1.x branch or the ExpressionEngine 2.x branch.  There are two different downloads - one for
each version.  Membrr does not require any other plugins - it will run on a default ExpressionEngine configuration.