## Template Tags

### &#123;exp:membrr:order_form&#125;

<pre class="example">
{exp:membrr:order_form redirect_url="/subscribe/thank_you"}
{if errors}
	&lt;div class=&quot;errors&quot;&gt;
	{errors}
	&lt;/div&gt;
{/if}
&lt;form method=&quot;{form_method}&quot; action=&quot;{form_action}&quot;&gt;
&lt;input type=&quot;hidden&quot; name=&quot;membrr_order_form&quot; value=&quot;1&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;plan_id&quot; value=&quot;{segment_2}&quot; /&gt;
&lt;!-- This credit card fieldset is not required for free or external checkout (e.g., PayPal Express Checkout) payment methods. --&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Billing Information&lt;/legend&gt;
	&lt;ul&gt;
		&lt;li&gt;
			&lt;label&gt;Credit Card Number&lt;/label&gt;
			&lt;input type=&quot;text&quot; name=&quot;cc_number&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Credit Card Name&lt;/label&gt;
			&lt;input type=&quot;text&quot; name=&quot;cc_name&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Expiry Date&lt;/label&gt;
			&lt;select name=&quot;cc_expiry_month&quot;&gt;{cc_expiry_month_options}&lt;/select&gt;&amp;nbsp;&amp;nbsp;&lt;select name=&quot;cc_expiry_year&quot;&gt;{cc_expiry_year_options}&lt;/select&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Security Code&lt;/label&gt;
			&lt;input type=&quot;text&quot; name=&quot;cc_cvv2&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
	&lt;/ul&gt;
&lt;/fieldset&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Billing Address&lt;/legend&gt;
	&lt;ul&gt;
		&lt;li&gt;
			&lt;label for=&quot;first_name&quot;&gt;First Name&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;first_name&quot; name=&quot;first_name&quot; maxlength=&quot;100&quot; value=&quot;{first_name}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;last_name&quot;&gt;Last Name&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;last_name&quot; name=&quot;last_name&quot; maxlength=&quot;100&quot; value=&quot;{last_name}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;company&quot;&gt;Company&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;company&quot; name=&quot;company&quot; value=&quot;{company}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;address&quot;&gt;Street Address&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;address&quot; name=&quot;address&quot; maxlength=&quot;100&quot; value=&quot;{address}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;address_2&quot;&gt;Address Line 2&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;address_2&quot; name=&quot;address_2&quot; maxlength=&quot;100&quot; value=&quot;{address_2}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;city&quot;&gt;City&lt;/label&gt;				
			&lt;input type=&quot;text&quot; id=&quot;city&quot; name=&quot;city&quot; maxlength=&quot;100&quot; value=&quot;{city}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;region&quot;&gt;State/Province&lt;/label&gt;
			&lt;select id=&quot;region&quot; name=&quot;region&quot;&gt;{region_options}&lt;/select&gt;&amp;nbsp;&amp;nbsp;&lt;input type=&quot;text&quot; id=&quot;region_other&quot; name=&quot;region_other&quot; value=&quot;{region_other}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;country&quot;&gt;Country&lt;/label&gt;
			&lt;select name=&quot;country&quot; id=&quot;country&quot;&gt;{country_options}&lt;/select&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;postal_code&quot;&gt;Zip/Postal Code&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;postal_code&quot; name=&quot;postal_code&quot; maxlength=&quot;100&quot; value=&quot;{postal_code}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;email&quot;&gt;Email Address&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;email&quot; name=&quot;email&quot; maxlength=&quot;100&quot; value=&quot;{email}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;phone&quot;&gt;Phone Number&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;phone&quot; name=&quot;phone&quot; value=&quot;{phone}&quot; /&gt;
		&lt;/li&gt;
	&lt;/ul&gt;
&lt;/fieldset&gt;
{if logged_out}
&lt;fieldset&gt;
	&lt;legend&gt;Registration&lt;/legend&gt;
	&lt;ul&gt;
		&lt;li&gt;
			&lt;label for=&quot;username&quot;&gt;Username&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;username&quot; name=&quot;username&quot; maxlength=&quot;100&quot; value=&quot;{username}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;password&quot;&gt;Password&lt;/label&gt;
			&lt;input type=&quot;password&quot; id=&quot;password&quot; name=&quot;password&quot; maxlength=&quot;100&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;password2&quot;&gt;Confirm Password&lt;/label&gt;
			&lt;input type=&quot;password&quot; id=&quot;password2&quot; name=&quot;password2&quot; maxlength=&quot;100&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
	&lt;/ul&gt;
&lt;/fieldset&gt;
{/if}
&lt;fieldset&gt;
	&lt;legend&gt;Coupon&lt;/legend&gt;
	&lt;ul&gt;
		&lt;li&gt;
			&lt;label for=&quot;coupon&quot;&gt;Coupon&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;coupon&quot; name=&quot;coupon&quot; maxlength=&quot;100&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
	&lt;/ul&gt;
&lt;/fieldset&gt;
{if captcha}
&lt;!-- Enable in Membrr > Settings --&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Human Verification&lt;/legend&gt;
	&lt;ul&gt;
		&lt;li&gt;
			{captcha}
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;captcha&quot;&gt;Enter the text above&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;captcha&quot; name=&quot;captcha&quot; maxlength=&quot;100&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
	&lt;/ul&gt;
&lt;/fieldset&gt;
{/if}
&lt;fieldset&gt;
	&lt;legend&gt;Review and Confirm Order&lt;/legend&gt;
	&lt;input type=&quot;submit&quot; value=&quot;Purchase My Subscription&quot;&gt;
&lt;/fieldset&gt;
&lt;/form&gt;
{/exp:membrr:order_form}
</pre>

This tag allows you to create a customized subscription order form with your own HTML.  It supplies default
and existing form values but you must create the `&lt;form&gt;` and `&lt;input&gt;` elements in your custom
order form.

This tag can also register a new user in the ExpressionEngine member database, providing certain conditions are met.  This allows you
to condense the subscription and registration process into one form.

#### `&lt;form&gt;` Configuration

*   `method="POST"` (use tag `{form_method}`)
*   `action=""` - The form will submit to the same page it resides on (use tag `{form_action}`)

#### Parameters

*   `redirect_url="/local_url/path"` or `redirect_url="http://www.external.com/url"` (optional,
	will overrride the "Redirect URL" setting specified in your Membrr Plan settings)

#### Variables

##### Billing Address Fields

Each variable holds the current billing address value (if one exists), or the submitted value of the field if the order form is
re-shown due to errors.

*   `{first_name}`
*   `{last_name}`
*   `{company}`
*   `{address}`
*   `{address_2}`
*   `{city}`
*   `{region}`
*   `{region_other}`
*   `{country}`
*   `{postal_code}`
*   `{email}`
*   `{phone}`

##### Data Variables

Use these to build your form with various pre-loaded data.

*   `{region_options}` - A string containing all of the &lt;option&gt; tags for use in the "region" &lt;select&gt; input element (e.g., "&lt;option value="AB"&gt;Alberta&lt;/option&gt;&lt;option value="BC"&gt;British Columbia&lt;/option&gt;).  If available in the billing address, the member's stored region is pre-selected in this string.
*   `{region_raw_options}` - An array containing each region in ID => Name pairings.
*   `{country_options}` - Identical to "region_options", except for country options and the "country" &lt;select&gt; element.
*   `{country_raw_options}` - An array containing each country in ID => Name pairings.
*   `{cc_expiry_month_options}` -  A string containing all of the &lt;option&gt; tags for use in the "cc_expiry_month" &lt;select&gt; element.
*   `{cc_expiry_year_options` -  A string containing all of the &lt;option&gt; tags for use in the "cc_expiry_year" &lt;select&gt; element.
*   `{gateway_options}` - A string containing all of the &lt;option&gt; tags for use in the "gateway" &lt;select&gt; element.
*   `{gateway_raw_options}`  - An array containing each gateway in ID => Name pairings.
*   `{errors_array}` - An array of all the errors in the processed form.  Only available upon submission.
*   `{errors}` - A string of all errors in the processed form, with each error in a &lt;div class="error"&gt; element.  Only available upon form submission.

#### Processed &amp; Required Form Fields

##### Basic Requirements

*   `plan_id`
*   `membrr_order_form` - Set to any non-empty value (e.g., "1").

##### Credit Card Requirements

If the subscription **is not free** and you are not using an external checkout (e.g., PayPal Express Checkout), credit card data must be passed:

*   `cc_number` - Credit card number
*   `cc_name` - Name on the credit card
*   `cc_expiry_month` - 2 digit representation of the year
*   `cc_expiry_year` - 4 digit representation of the expiry year
*   `cc_cvv2` - (Optional) The 3-4 digit security code on the credit card

##### Billing Address Fields

The following customer information fields should also be submitted, although some gateways do not require them (you will receive an error notice
if you submit without this information and it is required by your gateway):

*   `first_name</b>
*   <span class="tag">last_name</b>
*   <span class="tag">company</b>
*   <span class="tag">address</b>
*   <span class="tag">address_2`
*   `city`
*   `region` (for North American customers)
*   `region_other` (for non-North American customers)
*   `country`
*   `postal_code`
*   `email`
*   `phone`

##### Other Fields

*   `renew` - (optional) Specify the subscription ID of a subscription that is being renewed by this new subscription.  Usually passed as a `&lt;input type="hidden"&gt;` field.
*   `gateway` - (optional) Specify the OpenGateway gateway_id for the gateway that should process the transaction.  This will override the default settings in Membrr.
*   `coupon` - (optional) Allow a coupon code to be passed to reduce the price, add a free trial, etc.  These coupons are created in your OpenGateway control panel.

#### Combined Member Registration &amp; Subscription

If you would like to allow users to register an account while subscribing, the following modifications must be made to your form.

##### Processed &amp; Required Form Fields

*   `email` _must_ be passed as part of the billing address
*   `password` - Contains the user's desired password
*   `password2` - (optional) Verify their password by having the user type it twice.
*   `username` - (optional) Will override the user's email address as their username.
*   `screen_name` - (optional) Will override the user's username/email as their screen_name.

##### Variables

If the form returns errors when submitted, the following fields will contain the submitted values.

*   `{username}`
*   `{screen_name}`

##### Using Member Custom Fields

Each member custom field (created in your EE control panel at Members > Member Custom Fields) should be passed with the field's "name"
as the name of the member custom field.  For example, if you have a custom field named "favourite_colour", you would have the
following in your form: `&lt;input type="text" name="favourite_colour" value="{favourite_colour}" /&gt;`.

The `{favourite_colour}` tag in the code above will pre-populate the field if the form is submitted and has
errors that need correcting.

#### Using the CAPTCHA to stop subscription spam

Infrequently, users of Membrr experience robots who attempt to subscribe to free subscription plans over and over again,
creating an influx of dummy data in the system.  This can be prevented by enabling the CAPTCHA in the subscription form.
First, go to Membrr > Settings and enable the CAPTCHA option.  Then, like the example code above, use
`{captcha}` to drop the CAPTCHA image into your subscription form.  CAPTCHAs will not appear for
users who are registered and logged in when they access this form.

#### Implementation Notes, Frequently Asked Questions, and Warnings

##### Frequent Issues

*   If you are not allowing registration during subscription (i.e., with a "password" field passed), you must ensure that
	only logged-in users can see your subscription form with `{if logged_in}{/if}` tags around the
	form.
*   When passing a "renew" field to renew a subscription, the new subscription will be created, but the old subscription will remain
	until the billing term ends and the new subscription takes effect.

##### SSL Connections

You may use whatever means you would like to force (or not force) SSL on the order form page.  OpenGateway/Membrr will not force
this on you.  However, to force Membrr to communicate with OpenGateway via SSL, you will need to specify your API URL in Membrr's Settings
to use "https".  If you do not use "https", but OpenGateway's `ssl_active` setting is set to "TRUE" in `/app
/config/config.php`, you will receive an error, "A secure SSL connection is required (#1010)".  You must either fix your API URL
in Membrr's Settings or change that setting to FALSE in OpenGateway's config.php file.

##### Combined Registration &amp; Subscription

If you are passing a "password" field for registration, you should note the following:

*   Users will be placed in your default EE member group.  However, if you have Membrr configured to promote users to a new
	member group upon subscription, they will immediately be promoted.
*   If the user's subscription is rejected due to a billing failure, their account will immediately be deleted so that they
	can complete the form again (including registration).
*   If the user's subscription is rejected and they used an external checkout, their account will not be deleted.  So, when they return
	to the subscription form, they will be returning with an active, logged-in account, and should not re-register.
*   You should wrap the registration fields (i.e., "password", "username") in `{if logged_out}{/if}` tags
	so that logged-in users do not see them.

### &#123;exp:membrr:quick_order_form&#125;

This tag displays a pre-configured order form for a logged in user to purchase a subscription with.

Parameters:

*   **plan_id** - By default, a drop-down menu of all available subscription plans is shown.&nbsp; However, specifying a plan_id will remove this drop-down and display the name and rate of the plan selected.
*   **form_id** - Populates the &#8220;id&#8221; of the returned &lt;form&gt; element.
*   **ul_class** - Populates the &#8220;class&#8221; of the returned &lt;ul&gt; list of form fields.&nbsp; Useful for styling the form fields.
*   **button** - The value of the checkout button at the button of the form (Default: &#8220;Subscribe Now&#8221;).

Returns:

*   A complete order form.&nbsp; The form submits to itself at the same page it is being displayed on.&nbsp; If successful, it will redirect as per your plan settings.&nbsp; If unsuccessful, the form re-displays with errors at the top.&nbsp; Errors are displayed like so:

```
<div class="errors">
     <p>Credit card number invalid.</p>
         <p>No such plan exists.</p>
</div>
```

### &#123;exp:membrr:receipt&#125;

<pre class="example">
{exp:membrr:receipt date_format=&quot;M j, Y&quot;}
&lt;p&gt;Subscription ID: {subscription_id}&lt;/p&gt;
&lt;p&gt;Thank you for subscribing to {plan_name}!&lt;/p&gt;
&lt;p&gt;Your next payment date is &lt;b&gt;{next_charge_date}&lt;/b&gt;, when you will be charged &lt;b&gt;${amount}&lt;/b&gt;.&lt;/p&gt;
{/exp:membrr:receipt}
</pre>

Retrieves data about the latest payment for the purposes of showing a receipt to the user, post-order form.

Optional Parameters:

*   **date_format** - The format for returned full dates, as per the [PHP date() function standard](http://www.php.net/date).

Returns:

*   Single Variable Values:

    *   `{charge_id}`
    *   `{subscription_id}`
    *   `{amount}`
    *   `{next_charge_date}`
    *   `{plan_name}`
    *   `{member_id}`
    *   `{billing_first_name}`
    *   `{billing_last_name}`
    *   `{billing_address}`
    *   `{billing_address_2}`
    *   `{billing_city}`
    *   `{billing_region}`
    *   `{billing_country}`
    *   `{billing_postal_code}`
    *   `{billing_company}`
    *   `{billing_phone}`

### &#123;exp:membrr:update_form&#125;

<pre class="example">
{exp:membrr:update_form subscription_id=&quot;{segment_2}&quot; redirect_url=&quot;http://www.example.com/successful_update&quot;}
{if errors}
	&lt;div class=&quot;errors&quot;&gt;
	{errors}
	&lt;/div&gt;
{/if}
&lt;form method=&quot;{form_method}&quot; action=&quot;{form_action}&quot;&gt;
&lt;input type=&quot;hidden&quot; name=&quot;membrr_update_form&quot; value=&quot;1&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;subscription_id&quot; value=&quot;{subscription_id}&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;plan_id&quot; value=&quot;{plan_id}&quot; /&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Update Your Credit Card Information&lt;/legend&gt;
	&lt;ul&gt;
		&lt;li&gt;
			&lt;label&gt;Credit Card Number&lt;/label&gt;
			&lt;input type=&quot;text&quot; name=&quot;cc_number&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Credit Card Name&lt;/label&gt;
			&lt;input type=&quot;text&quot; name=&quot;cc_name&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Expiry Date&lt;/label&gt;
			&lt;select name=&quot;cc_expiry_month&quot;&gt;{cc_expiry_month_options}&lt;/select&gt;&amp;nbsp;&amp;nbsp;&lt;select name=&quot;cc_expiry_year&quot;&gt;{cc_expiry_year_options}&lt;/select&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Security Code&lt;/label&gt;
			&lt;input type=&quot;text&quot; name=&quot;cc_cvv2&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
	&lt;/ul&gt;
&lt;/fieldset&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Update Your Billing Address&lt;/legend&gt;
	&lt;ul&gt;
		&lt;li&gt;
			&lt;label for=&quot;first_name&quot;&gt;First Name&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;first_name&quot; name=&quot;first_name&quot; maxlength=&quot;100&quot; value=&quot;{first_name}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;last_name&quot;&gt;Last Name&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;last_name&quot; name=&quot;last_name&quot; maxlength=&quot;100&quot; value=&quot;{last_name}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;address&quot;&gt;Street Address&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;address&quot; name=&quot;address&quot; maxlength=&quot;100&quot; value=&quot;{address}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;address_2&quot;&gt;Address Line 2&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;address_2&quot; name=&quot;address_2&quot; maxlength=&quot;100&quot; value=&quot;{address_2}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;city&quot;&gt;City&lt;/label&gt;				
			&lt;input type=&quot;text&quot; id=&quot;city&quot; name=&quot;city&quot; maxlength=&quot;100&quot; value=&quot;{city}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;region&quot;&gt;State/Province&lt;/label&gt;
			&lt;select id=&quot;region&quot; name=&quot;region&quot;&gt;{region_options}&lt;/select&gt;&amp;nbsp;&amp;nbsp;&lt;input type=&quot;text&quot; id=&quot;region_other&quot; name=&quot;region_other&quot; value=&quot;{region_other}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;country&quot;&gt;Country&lt;/label&gt;
			&lt;select name=&quot;country&quot; id=&quot;country&quot;&gt;{country_options}&lt;/select&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;postal_code&quot;&gt;Zip/Postal Code&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;postal_code&quot; name=&quot;postal_code&quot; maxlength=&quot;100&quot; value=&quot;{postal_code}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;email&quot;&gt;Email Address&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;email&quot; name=&quot;email&quot; maxlength=&quot;100&quot; value=&quot;{email}&quot; /&gt;
		&lt;/li&gt;
	&lt;/ul&gt;
&lt;/fieldset&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Review and Save Changes&lt;/legend&gt;
	&lt;input type=&quot;submit&quot; value=&quot;Update Billing Information&quot;&gt;
&lt;/fieldset&gt;
&lt;/form&gt;
{/exp:membrr:update_form}
</pre>

This tag allows you to create a customized form for the user to update their credit card information and/or **upgrade/downgrade their subscription plan**.
By updating the credit card
information associated with an existing subscription, the user can keep their subscription alive with a new credit card.  It supplies default
and existing form values but you must create the &lt;form&gt; and &lt;input&gt; elements in your custom form.

Note: The subscription ID will change when the user updates their subscription.

Parameters:

*   **subscription_id** - The subscription to update.
*   **redirect_url** - The user is redirected here after they update their subscription.

The form must be configured in the following manner:

*   Method: "POST"
*   Action: "" (leave blank so the form submits to the page which the order form is on)

_It is not necessary to include the customer address fields in this form_.  If they are not included, the billing address will simply stay as is.
However,
if you are showing these fields, the user should be able to select their region in a "region" dropdown if they are from North America.
If they are outside of North America, they should populate a text input field named "region_other".
It is recommended to use JavaScript to show/hide the "region_other" input box, depending on their
country selection.

Between the two `{exp:membrr:update_form}` `{/exp:membrr:update_form}` tags,
you can use any of the following variables in the building of your form:

*   **first_name**
*   **last_name**
*   **address**
*   **address_2**
*   **city**
*   **region**
*   **region_other**
*   **country**
*   **postal_code**
*   **email**
*   **region_options** - A string containing all of the &lt;option&gt; tags for use in the "region" &lt;select&gt; input element (e.g., "&lt;option value="AB"&gt;Alberta&lt;/option&gt;&lt;option value="BC"&gt;British Columbia&lt;/option&gt;).  If available, the member's stored region is pre-selected in this string.
*   **region_raw_options** (EE2 only) - An array containing each region in ID => Name pairings.
*   **country_options** - Identical to "region_options", except for country options and the "country" &lt;select&gt; element.
*   **country_raw_options** (EE2 only) - An array containing each country in ID => Name pairings.
*   **cc_expiry_month_options** -  A string containing all of the &lt;option&gt; tags for use in the "cc_expiry_month" &lt;select&gt; element.
*   **cc_expiry_year_options** -  A string containing all of the &lt;option&gt; tags for use in the "cc_expiry_year" &lt;select&gt; element.
*   **errors_array** (EE2 only) - An array of all the errors in the processed form.  Only available upon submission.
*   **errors** - A string of all errors in the processed form, with each error in a &lt;div class="error"&gt; element.  Only available upon form submission.
*   **form_action - The current URL**
*   **form_method - "POST"**

Upon submission of the form, the following values are **required**:

*   **subscription_id**
*   **membrr_update_form** - Set to any value
*   **cc_number** - Credit card number
*   **cc_name** - Name on the credit card
*   **cc_expiry_month** - 2 digit representation of the year
*   **cc_expiry_year** - 4 digit representation of the expiry year
*   **cc_cvv2** - (Optional) The 3-4 digit security code on the credit card

The following customer information fields _can also be submitted in the order form submission_, but they are **NOT** required:

*   **first_name**
*   **last_name**
*   **address**
*   **address_2**
*   **city**
*   **region** (for North American customers)
*   **region_other** (for Non-North American customers)
*   **country**
*   **postal_code**
*   **email**

### &#123;exp:membrr:subscriptions&#125;

Returns subscription entries for the logged-in user in the format of the HTML between the tags.&nbsp; A number of tags and conditionals can be used between the tags.

Optional Parameters:

*   **member_id** - Specify a specific member to return subscription records for (default: logged in user)
*   **date_format** - The format for returned full dates, as per the [PHP date() function standard](http://www.php.net/date).
*   **status** - Set to "active" or "inactive" to retrieve only those plans.
*   **inactive** - Set to &#8220;1&#8221; to retrieve only expired subscriptions.
*   **id** - Specify a particular subscription ID # to return only that subscription.

Returns:

*   Single Variable Values:

    *   `{subscription_id}`
    *   `{recurring_fee}`
    *   `{date_created}`
    *   `{date_cancelled}` (if exists) - The date the subscription was cancelled by the user.
    *   `{next_charge_date}` (if exists) - The date of the next charge
    *   `{end_date}` (if exists) - The date this subscription will expire
    *   `{plan_name}`
    *   `{plan_id}`
    *   `{plan_description}`
    *   `{card_last_four}` - Returns last 4 digits of credit card, if available
    *   `{channel}` (if exists)
    *   `{entry_id}` (if exists)
    
*   Conditionals:

        *   `active` - Subscription is still actively recurring.
*   `active` - Subscription is still actively recurring.
    
    ```
    {if active}Your next charge will be {next_charge_date}{/if} 
    ```

    *   `renewed` - Subscription has been renewed.  Another subscription will simultaneously be active (the renewing subscription).

	```
	{if renewed}This subscription has been renewed.{/if}
	```

    *   `cancelled` - Subscription was cancelled by the user

    ```
    {if user_cancelled}You cancelled this subscription on {date_cancelled} and it will expire on {end_date}{/if} 
    ```
    
    *   `expired` - Subscription has expired completely.

    ```
    {if expired}This subscription has expired, either by user cancellation or failed payment.  But its over.  And it ended on {end_date}{/if} 
    ```

### &#123;exp:membrr:payments&#125;

Returns payment history for the logged-in user in the format of the HTML between the tags.&nbsp; A number of tags can be used between the tags.

Optional Parameters:

*   **member_id** - Specify a specific member to return payment records for (default: logged in user)
*   **date_format** - The format for returned full dates, as per the [PHP date() function standard](http://www.php.net/date).
*   **id** - Specify a particular charge ID # to return only that charge.
*   **offset** - Number of records to offset the results from (e.g., offset of &#8220;5&#8221; returns from 6th record on).
*   **limit** - Number of records to return in total.
*   **subscription_id** - Specify a particular subscription ID # to return only charges pertaining to that subscription.

Returns:

*   Single Variable Values:

    *   `{charge_id}`
    *   `{subscription_id}`
    *   `{amount}`
    *   `{date}`
    *   `{plan_name}`
    *   `{plan_id}`
    *   `{plan_description}`
    *   `{channel}` (if exists)
    *   `{entry_id}` (if exists)

### &#123;exp:membrr:billing_address&#125;

Retrieve and display the current billing address (entered on an order form) of the logged-in member.

Returns:

*   `first_name`
*   `last_name`
*   `address`
*   `address_2`
*   `city`
*   `region`
*   `region_other`
*   `country`
*   `postal_code`
*   `company`
*   `phone`

### &#123;exp:membrr:plans&#125;

Returns information on subscription plan(s).

Parameters:

*   **id** - Specify a particular plan ID # to return only that plan.  You may also specify multiple plan ID's in the format of "1001|1002|1003" to retrieve information for multiple plan ID's.
*   **for_sale** - Specify &#8220;1&#8221; for active (for sale) plans and &#8220;0&#8221; for inactive plans.&nbsp; By default, only active plans are retrieved.

Returns:

*   Single Variable Values:

    *   `{plan_id} (the Membrr Plan ID)`
    *   `{api_plan_id}` (the OpenGateway Plan ID)
    *   `{name}`
    *   `{description}`
    *   `{interval}` (e.g., &#8220;30&#8221;)
    *   `{free_trial}` (e.g., &#8220;0&#8221; for no free trial, &#8220;5&#8221; for a 5-day free trial)
    *   `{occurrences}` (e.g., &#8220;0&#8221; for unlimited, &#8220;12&#8221; for 12 total charges)
    *   `{price}` (e.g., &#8220;9.95&#8221;)
    *   `{initial_charge}` (e.g., &#8220;4.95&#8221;)
    *   `{total_subscribers}` (e.g., &#8220;40&#8221;)

### &#123;exp:membrr:subscribed&#125;

<pre class="example">
	{exp:membrr:subscribed plan="1001|1002"}
	You are actively subscribed to one of our 2 plans!
	{/exp:membrr:subscribed}

	{exp:membrr:subscribed plan="1005"}
	You are actively subscribed to plan #1005!
	{/exp:membrr:subscribed}

	{exp:membrr:subscribed}
	You are actively subscribed to any one of our plans!
	{/exp:membrr:subscribed}

	{exp:membrr:subscribed member_id="2143" plan="1004"}
	This member is actively subscribed to this specific plan!
	{/exp:membrr:subscribed}
</pre>

Only displays the content between the tags if the user is subscribed to the plan(s) listed.  Or, if no plan parameter is passed, to any subscription.

Parameters:

*   **plan** (optional) - Either one single plan_id (e.g., "1001") or multiple plan ID's separated by pipes (e.g., "1001|1002|1005").
*   **member_id** (optional) - A member's ID (default: use the actively logged in member)

Returns:

*   Returns the tag HTML if the user has a subscription to (one of) the plan(s).&nbsp; If not, nothing is returned.

### &#123;exp:membrr:not_subscribed&#125;

<pre class="example">
	{exp:membrr:not_subscribed plan="1001|1002"}
	You aren't subscribed to either of our 2 plans!
	{/exp:membrr:not_subscribed}

	{exp:membrr:not_subscribed plan="1005"}
	You aren't subscribed to plan #1005!
	{/exp:membrr:not_subscribed}

	{exp:membrr:not_subscribed}
	You are not actively subscribed to any one of our plans!
	{/exp:membrr:not_subscribed}
</pre>

Only displays the content between the tags if the user is not subscribed to the plan(s).  Or, if no plan parameter is passed, to any subscription.

Parameters:

*   **plan** (optional) - Either one single plan_id (e.g., "1001") or multiple plan ID's separated by pipes (e.g., "1001|1002|1005").

Returns:

*   Returns the tag HTML if the user does not have a subscription to (any of) the plan(s).&nbsp; If they do have a subscription, nothing is returned.

### &#123;exp:membrr:cancel&#125;

Cancels an active subscription.&nbsp; The subscription will expire when the period that the user has paid for expires.

Parameters:

*   **id** - The ID # of the subscription to cancel.

Returns:

*   Returns the HTML between the tags.&nbsp; It also implements the following conditionals to be used for confirmation/failure.

    *   `cancelled` - The subscription was cancelled successfully.
    *   `failed` - The cancellation failed either due to system error, the user not owning the subscription, or the subscription already being cancelled.

Example:

    <pre class="example"><div class="codeblock">`<span style="color: #000000">
<span style="color: #0000BB">{exp</span><span style="color: #007700">:</span><span style="color: #0000BB">membrr</span><span style="color: #007700">:</span><span style="color: #0000BB">cancel id</span><span style="color: #007700">=</span><span style="color: #DD0000">"{segment_3}"</span><span style="color: #0000BB">}
{if cancelled}Your subscription was cancelled</span><span style="color: #007700">!</span><span style="color: #0000BB">{</span><span style="color: #007700">/</span><span style="color: #0000BB">if}
{if failure}Subscription could not be cancelled</span><span style="color: #007700">.</span><span style="color: #0000BB">{</span><span style="color: #007700">/</span><span style="color: #0000BB">if}
{</span><span style="color: #007700">/</span><span style="color: #0000BB">exp</span><span style="color: #007700">:</span><span style="color: #0000BB">membrr</span><span style="color: #007700">:</span><span style="color: #0000BB">cancel} </span>

    </span>
`</div></pre>

### &#123;exp:membrr:has_subscription_for_channel&#125;

Returns the tag data if the user has permission to post to the protected channel.&nbsp; Useful for wrapping around a SAEF for a protected channel.  Note: For EE1.6.x, this tag is **{exp:membrr:has_subscription_for_weblog weblog="your_weblog"}**.

Parameters:

*   **channel** (required) - The numeric ID or name of the protected channel (e.g., &#8220;22&#8221; or &#8220;self_serve_ads&#8221;).

Returns:

*   The HTML between the tags if the user has permission to post to this protected channel, or else nothing.

### &#123;exp:membrr:no_subscription_for_channel&#125;

Returns the tag data if the user does not have permission to post to the protected channel.&nbsp; Useful for wrapping around an order form for a subscription, in conjunction with the above tag and an SAEF.  Note: For EE1.6.x, this tag is **{exp:membrr:no_subscription_for_weblog weblog="your_weblog"}**.

Parameters:

*   **channel** (required) - The numeric ID or name of the protected channel (e.g., &#8220;22&#8221; or &#8220;self_serve_ads&#8221;).

Returns:

*   The HTML between the tags if the user does not have permission to post to this protected channel, or else nothing.