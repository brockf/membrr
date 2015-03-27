Membrr Import
------------------------------------

What this script does:

* Creates customer, payment, and subscription records at OpenGateway
* Creates payment and subscription records at Membrr and associates the records with an existing member_id (i.e., members must already exist in EE)
* Moves the users into their appropriate user groups, if you are using the promotion/demotion feature of Membrr subscriptions

To use:

1) Open the membrr_import.php file and modify the configuration.  Make sure your database, client_id, and gateway_id are all specified and accurate.

2) Place in a directory with your CSV file to import.  This file should be named "member_import.csv".

3) Modify the code above the Importer class so that it loads the proper CSV file, extracts data from the fields in the proper order, and uses the Importer class's methods to do the actual import.

4) Upload the CSV file and membrr_import.php file to your web server.

5) Access yourdomain.com/subfolder/membrr_import.php.

6) If you experience any errors, please contact brock@electricfunction.com with the exact output of the script.

Thank you!  Happy importing!