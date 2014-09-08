## Installation & Configuration

To set your site up with Membrr, you must first install the OpenGateway billing engine in a sub-folder or sub-domain on
your web server.  Then, you can upload Membrr to your ExpressionEngine install to complete the process.

### Installing OpenGateway

1.  [Download the latest OpenGateway release](http://www.github.com/electricfunction/opengateway).
2.  Upload all the application files to your web server, into a subfolder or subdomain so that you don't overwrite your website's files.
3.  Rename `/system/opengateway/config/config.example.php` to `/system/opengateway/config/config.php`.
4.  If the `.htaccess` file doesn't exist in your root folder, rename "1.htaccess" to ".htaccess".
5.  Access the "install/" directory on your web server, e.g., http://www.example.com/install.  **This directory does not actually
	exist but the URL routing system in OpenGateway will direct you automatically.**  If you uploaded your files to your root domain
	folder at example.com, you would access example.com/install.  If you uploaded your files to a sub-directory, you would access
	example.com/subdirectory/install.  The same applies to subdomains.
6.  Follow the 2-step installation wizard to configure your database and create your cron jobs.
7.  Login to the OpenGateway control panel with your username/password, go to "Settings > API Access",
	and write down your API credentials for use later.
8.  **Important Note**:  If you are using the same OpenGateway install for two installations of Membrr, or Membrr and
	[EE Donations](http://www.eedonations.com), you should create one OpenGateway client account for each installation
	to avoid serious conflicts.  Each account will have unique API access credentials.

### Installing the Membrr Plugin

Now that your OpenGateway billing engine is setup, you can install Membrr and connect it to your OpenGateway software.

1.  [Download the latest Membrr plugin for your ExpressionEngine version](http://www.github.com/electricfunction/membrr).
2.  For ExpressionEngine 2: Unzip and upload the `/membrr` folder to `/system/expressionengine/third_party/`.  If you have renamed your system
	folder, use that folder instead.
3.  For ExpressionEngine 1.6.x: Unzip and upload the `/modules/membrr` folder to `/system/modules/`.  Unzip and upload
	`/extensions/ext.membrr_extension.php` to `/system/extensions/`.  If you have renamed your system
	folder, use that folder instead.
4.  Login to your ExpressionEngine control panel and go to Addons > Modules.  Install the Membrr module.
5.  Next, navigate to your Extensions manager and enable the Membrr extension.

Now you are ready to go with Membrr!