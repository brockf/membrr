## Creating Subscription Plans

Subscription plans are created in your OpenGateway control panel under "Recurring Plans".  After they are created here,
they can be imported into your ExpressionEngine website through the Membrr module control panel.

### Creating the plan in OpenGateway

When creating a subscription plan, the following details can be configured:

*   **Plan Name** - This name will be the name of the plan in OpenGateway.  When you import this plan into
	Membrr, you can rename it.
*   **Price** - How much should the user be charged upon each renewal?
*   **Charge Interval** - How many days should there be between recurring charges?
*   **Total Occurrences** - Most subscriptions run indefinitely.  However, you may want to specify
	the number of renewals to charge before the subscription expires.
*   **Free Trial Period** - If you specify a free trial period, the user will not be charged until X days
	after they purchase their subscription.  Billing information is collected upon purchase, but not charged
	until the end of the free trial period.
*   **Notification URL** - Leave this blank, as it will be overwritten by Membrr when you import the plan
	into your website.

### Importing the plan into Membrr

To make this plan available for purchase in your ExpressionEngine website, you'll have to import the plan
from OpenGateway into Membrr.  When doing so, you can specify additional information about the plan:

*   **Plan Name** - You can enter a new name for the plan here.  This allows you to have the same core plan (e.g., same price and charge interval)
	under multiple names in your ExpressionEngine website.
*   **Public Description** - If you want to create a page that lists all of your plans, this description field can be used
	to describe each plan's benefits, features, etc.
*   **Initial Charge** - If you want the user to be charged a different rate at the beginning of their subscription, you can
	set this value here.  It can be $0.00 but it is suggested to just use a Free Trial period.  This charge can also be referred to as a
	setup fee.
*   **Member Group after Purchase** - (Optional) What member group should the user be moved to when they are a subscriber
	to this plan?
*   **Member Group after Expiration** - (Optional) What member group should the user be moved to after their subscription to this
	plan expires?
*   **Redirect URL after Purchase** - After a successful order for this plan is placed, where should the user be redirected?
	This is likely a thank you page.
*   **For Sale?** - You can deactivate plans' availability for purchasing here.

### Notes on Subscription Plans

When a user is subscribed to a plan, editing that plan will not update their subscription.  They will only adopt the new plan's
settings when their existing subscription expires or is cancelled and they re-subscribe to the plan.