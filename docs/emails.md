## Creating Automatic Emails

Emails can be sent out to the customer, yourself, or any other email address when certain actions are performed.  The following actions
can trigger email notifications:

*   **Charge** - A one-time payment (i.e., not linked to a subscription) is processed.
*   **Recurring Charge** - A payment linked to a subscription is processed.
*   **Recurring Expiration** - A subscription ends due to payment failure or end of subscription term.
*   **Recurring Cancellation** - A subscription is cancelled by the user (this hook fires immediately upon cancellation, the expiration hook fires when their term ends).
*   **Recurring to Expire in a Week** - A subscription will expire in a week.
*   **Recurring to Expire in a Month** - A subscription will expire in a month.
*   **Recurring to Autocharge in a Week** - A subscription payment will be attempted in a week.
*   **Recurring to Autocharge in a Month** - A subscription payment will be attempted in a month.
*   **New Customer** - A new customer record is created. This occurs before a subscription or charge is attempted, and thus is not useful as a "successful subscription"-type email (use 'New Recurring' instead).
*   **New Recurring** - A new subscription is successfully started.

Emails are created and managed in your OpenGateway control panel.

### Standard Configuration

Most Membrr users will have 4 emails setup using the triggers above. A **New Recurring** email will thank users for subscribing (these may be
subscriptions or renewals). A **Recurring Charge** email will act as an invoice for a single payment. A **Recurring to Expire in a Week** payment
will attempt to have users renew their subscriptions before they expire. Finally, a **Recurring Expiration** email will notify users that their
subscription has ended, and offer renewal options to become a subscriber again.

### Creating Emails in OpenGateway

In your OpenGateway control panel, go to "Settings" > "Emails" to create and manage your automatic emails.

As stated above, the first criteria you'll specify for your email is when it should be sent.  Emails are
associated with a particular event such as a cancellation or subscription.  They can also be associated
with a specific subscription plan.

When creating an email, you can include dynamic information like the customer's name, the charge amount/date,
the subscription plan name, etc.  Each event has a unique set of data which will be available for use in your
email subject and body.  When creating a plan, there will be a list of all dynamic data tags (e.g., `[[CUSTOMER_LAST_NAME]]`)
displayed beside the email editor, for use as a reference.

### Formatting Dates in Emails

Date variables are often available in emails for things like the Next Charge Date, Order Date, etc.  You are able to specify the
format for these dates.  For example, while the date variable may be "2010-09-19", you can print this as:

*   September 19, 2010
*   Sep 19, 2010
*   2010.09.19
*   19-Sep-2010
*   etc.

Formatting is done by passing a parameter with the variable.  For Example: `[[NEXT_CHARGE_DATE|"M d, Y"]]`.  The second parameter (in quotation marks)
tells the application how to display the dates.  You can specify any date format using either of PHP's [date()](http://www.php.net/date)
and [strftime()](http://www.php.net/strftime) formatting styles.