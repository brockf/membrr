## Offline Payments &amp; Invoices

If you do not want to (always) use OpenGateway + Membrr to automatically charge a credit card or PayPal account, you have the option
to accept offline payment methods and/or use a more standard invoicing system.

### The Offline Payment Gateway

This gateway is very simple.  When it receives a charge, it simply records the charge in the records as paid.  It does the same for subscriptions.
The subscriptions will last until they expire or are cancelled.  Payments are assumed to be made via an offline method.  There are no invoices.

It's a great and simple way to record offline payments in the system.  However, it may not meet the needs of all users and so we do offer a
more standard invoice-based alternative...

### Online Invoicing with [FreshBooks](http://www.freshbooks.com)

As of OpenGateway v1.5, there is now support for a [FreshBooks](http://www.freshbooks.com) invoicing payment gateway.  This gateway generates invoices for each one-time and
recurring charge right in your existing FreshBooks account.  It also maintains a customer database in your FreshBooks account and properly
links each invoice to its customer record.

Once the invoices are created, you can either send out the invoice to the user for them to pay via an offline or online payment
method (setup within FreshBooks) _or_ you can use the invoices internally to track the offline payments you should be receiving
via cheque, money order, bank transfer, etc.

FreshBooks + OpenGateway is a powerful invoicing and subscription billing application.  And, with Membrr, it all ties perfectly into
your ExpressionEngine website.