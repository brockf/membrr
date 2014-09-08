## Importing Members &amp; Subscriptions into Membrr

For users with an existing subscriber base, you may want to import your subscribers into Membrr.  This involves:

*   Creating customer records in OpenGateway
*   Creating subscription records in OpenGateway
*   Creating payment records in OpenGateway (Optional)
*   Creating subscription records in Membrr
*   Creating payment records in Membrr (Optional)

Although there currently is not an import wizard in the control panel, there is a customizable PHP script that will
import all of the above information - and link it together properly - based on the data in a
[CSV](http://en.wikipedia.org/wiki/Comma-separated_values) file.  Essentially, this PHP script includes a 
PHP class that has simple methods for importing all of this data.  It also includes an example portion of the script
that will parse a typical CSV file.

## Using the Membrr Import PHP Script

[Click here to download the PHP script](http://help.electricfunction.com/kb/membrr/how-do-i-import-subscriptions-into-membrr).

Then, do the following:

1.  Unzip the archive.
2.  Configure the script to connect to your database(s) properly.
3.  Edit the `membrr_import.php` script so that it is parsing your CSV file and calling the class methods properly.
4.  Access the import script in your browser.
5.  Fix any errors.  Check the import!

## Notes

*   No emails will be sent out regarding payments or subscriptions created in the importer.  This allows you to clear out all data
	after a failed import without annoying your subscribers.