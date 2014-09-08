## Channel/Weblog Protection

**Note:** This documentation references "channels", as per ExpressionEngine 2.  However, for ExpressionEngine 1.6.x users who are using "weblogs",
the same information applies.

One of the best ways to monetize a website is to give users the ability to post subscription
content on your website, such as job listings, directory listings, or advertisements.  Membrr
lets you link channel posts created in a Standalone Entry Form to subscriptions.  When the subscription
expires, the post's status is set to whatever you would like (e.g., Closed).  The entire setup is
automatic and only takes a few minutes to integrate.

### Step 1: Create a Channel

If you haven't already created the channel you want to protect, do so now.  You can include custom fields or custom statuses like any other channel.  There are no limitations.

Be sure to set the channel permissions so that members can see the SAEF at Admin > Members and Groups > Member Groups > Edit Member Group > Members.

### Step 2: Protect the Channel with Membrr

In your EE Membrr control panel, select the Channel Protector tab.  Beneath the table, select the "Protect a Channel" button.  Select the channel you would like to protect and configure as you wish.  Options are described below:

*   **Required subscription(s)** - You may select multiple subscriptions which will grant the user access to post to this channel.  If a user does not have one (or more) of these subscriptions active in their account, their post will be denied.  Template tags will let you verify the user's subscription status prior to displaying a Standalone Entry Form (SAEF).
*   **Number of posts per subscription** - How many posts should the user be allowed to make for each of their matching subscriptions?  This can be any set number or "unlimited".
*   **If the linked subscription expires, set post status to** - When a subscription with a linked post (or multiple posts) expires, you have the option of modifying the post status.  For example, while a post may initially have a status of "Open", you may want to set the status to "Closed" upon subscription expiration.  This way, the content will be removed from your website when the subscription expires.  This field will reflect whatever custom status fields you are using for your channel.
*   **Redirect URL** - If the user does not have a proper subscription but manages to make a post to the SAEF, he/she will be rejected and redirected to the URL specified here.  It's recommended that this URL includes a template with a {exp:membrr:order_form} tag on it, so that the user can purchase a subscription.

### Step 3: Create a SAEF Form

Create a SAEF form for the user to post to this channel.  This entry form will be like any other form, except you should use Membrr template tags to insure that the user has the proper subscription before completing the form.

<pre class="example">
{logged_in}
	{exp:membrr:has_subscription_for_channel channel="my_channel"}
		<!-- STANDALONE ENTRY FORM HERE -->
	{/exp:membrr:has_subscription_for_channel}
{/logged_in}
</pre>

### Step 4: Use EE template tags for subscription management

Besides the tags used in the above example, you'll want to use the other Membrr for EE template tags to integrate subscriptions into your website.  The following notes are particularly pertinent to those using Channel Protection:

*   In `{exp:membrr:subscriptions}` and `{exp:membrr:payments}` calls, subscriptions linked to Channel entries will return two fields that normal subscriptions will not: `{channel}` and `{entry_id}`.  So, if you are creating a listing of active subscriptions or a payment history, you can show or link to the posts linked to this subscription.
*   You can use SAEF forms for editing, as well.  The plugin will know if you are submitting an SAEF with an existing "entry_id" and will allow the post to go through regardless of subscription status.
*   When loading an SAEF form for an entry that you want to link to a specific subscription, place a hidden input field called "subscription_id" with the `{subscription_id}` of the subscription.  This will tell Membrr to use this exact subscription, whether other possible subscriptions exist or not.