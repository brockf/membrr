## Coupons

Coupons are available as of Membrr version 1.3 and OpenGateway version 1.62.

Coupons are codes that can be sent via an order form built using the [{exp:membrr:order_form}](/docs/template_tags.md) template tag.  They
can offer special discounts or adjustments to subscriptions, including the ability to:

*   Reduce the initial payment amount
*   Reduce the recurring payment amount
*   Reduce both the initial/recurring charge amounts
*   Add/modify the free trial (in days)

Coupons are built and managed in your OpenGateway control panel.  Coupon values can be either a percentage of the current amount being charged or a set dollar amount.

They can also be limited specific subscription plans.

**To pass a coupon, simply include a blank text input field in your form with the name "coupon".  Any text string passed to the form
processor in this box will be validated at OpenGateway so you don't have to validate on your end.**