## Extension Hooks

### membrr_subscribe

Allows you to perform your own custom actions upon a new subscription.

**Hook File**

`class.membrr_ee.php`

**Hook Parameters**

*   `$member_id` - The Member ID of the subscribing user.
*   `$subscription_id` - The subscription ID for the new subscription.
*   `$plan_id` - The Membrr plan ID of the new subscription.
*   `$end_date` - If the subscription has a set end_date, it will be passed here.&nbsp; If not, this value will be &#8220;0000-00-00&#8221;.

**Hook Returns Data?**

No

**Appearance of Hook in Code**

```
if ($this->EE->extensions->active_hook('membrr_subscribe') == TRUE)
{
     $this->EE->extensions->call('membrr_subscribe', $member_id, $response['recurring_id'], $plan['id'], $end_date);
     if ($this->EE->extensions->end_script === TRUE) return $response;
} 
```

### membrr_pre_subscribe

Allows you to add to or modify the $recur OpenGateway API call object prior to the &#8220;Recur&#8221; API request, and modify any of the soon-to-be created subscription&#8217;s details.

**Hook File**

`class.membrr_ee.php`

**Hook Parameters**

*   `$recur` - The OpenGateway class object storing the developed Recur API request.
*   `$member_id` - The Member ID of the subscribing user.
*   `$plan_id` - The Membrr plan ID for the subscription.
*   `$recurring_charge` - The amount of the ongoing subscription payment.
*   `$first_charge` - The amount of the initial payment today.
*   `$end_date` - The date of the next charge to be made.

**Hook Returns Data?**

No

**Appearance of Hook in Code**

```
if ($this->EE->extensions->active_hook('membrr_pre_subscribe')== TRUE)
{
     $this->EE->extensions->call('membrr_pre_subscribe', $recur, $member_id, $plan_id, $recurring_charge, $first_charge, $end_date, $next_charge_date);
     if ($this->EE->extensions->end_script === TRUE) return FALSE;
} 
```

### membrr_payment

Allows you to perform your own custom actions upon a new payment.

**Hook File**

`class.membrr_ee.php`

**Hook Parameters**

*   `$member_id` - The Member ID of the subscribed user.
*   `$subscription_id` - The new subscription ID.
*   `$plan_id` - The Membrr plan ID of the subscription.
*   `$charge_id` - The ID of the payment.
*   `$next_charge_date` - The date of the next charge to be made.

**Hook Returns Data?**

No

**Appearance of Hook in Code**

```
if ($this->EE->extensions->active_hook('membrr_payment')== TRUE)
{
     $subscription = $this->GetSubscription($subscription_id);
     $this->EE->extensions->call('membrr_payment', $subscription['member_id'], $subscription_id, $subscription['plan_id'], $charge_id, $subscription['next_charge_date']);
     if ($this->EE->extensions->end_script === TRUE) return $response;
} 
```

### membrr_update_cc

If using the recurring ID's, this hook will let you update your old recurring ID's with the new recurring ID's after someone updates
their credit card.

**Hook File**

`class.membrr_ee.php`

**Hook Parameters**

*   `$member_id` - The Member ID of the subscribed user.
*   `$old_recurring_id` - The ID of their old subscription, pre-update
*   `$new_recurring_id` - The ID of their new subscription, post-update

**Hook Returns Data?**

No

**Appearance of Hook in Code**

```
// call "membrr_update_cc" hook with: $member_id, $old_recurring_id, $new_recurring_id

if ($this->EE->extensions->active_hook('membrr_update_cc') == TRUE)
{
	$this->EE->extensions->call('membrr_update_cc', $member_id, $old_recurring_id, $new_recurring_id);
    if ($this->EE->extensions->end_script === TRUE) return FALSE;
}
```

### membrr_cancel

Allows you to perform your own custom actions upon a cancellation (i.e., at the time a user cancels or when the card is declined).

**Hook File**

`class.membrr_ee.php`

**Hook Parameters**

*   `$member_id` - The Member ID of the subscribed user.
*   `$subscription_id` - The subscription ID for the cancelled subscription.
*   `$plan_id` - The Membrr plan ID of the plan.

**Hook Returns Data?**

No

**Appearance of Hook in Code**

```
if ($this->EE->extensions->active_hook('membrr_cancel')== TRUE)
{
     $this->EE->extensions->call('membrr_cancel', $subscription['member_id'], $subscription['id'], $subscription['plan_id'], $subscription['end_date']);
     if ($this->EE->extensions->end_script === TRUE) return;
} 
```

### membrr_expire

Allows you to perform your own custom actions upon an expiration.&nbsp; This is when the subscription actually comes to an end.&nbsp; For example, the user may cancel on April 10th but, if they paid for a subscription until April 20th, the expiration won&#8217;t occur until the 20th while the cancellation occurs on the 10th.

**Note:** If you are using Membrr to promote/demote users to usergroups upon subscription/expiration, the system won't execute this hook for a subscription expiration if the user has an active subscription that promotes to the user to the same group as the plan that is currently expiring.  This stops false expiration triggers and usergroup moves for users who have re-subscribed or renewed their subscriptions during the duration of their previous subscription.

**Hook File**

`class.membrr_ee.php`

**Hook Parameters**

*   `$member_id` - The Member ID of the subscribed user.
*   `$subscription_id` - The subscription ID of the expiring subscription.
*   `$plan_id` - The Member ID of the subscribing user.

**Hook Returns Data?**

No

**Appearance of Hook in Code**

```
if ($this->EE->extensions->active_hook('membrr_expire')== TRUE)
{
     $this->EE->extensions->call('membrr_expire', $row['member_id'], $row['recurring_id'], $row['plan_id']);
     if ($this->EE->extensions->end_script === TRUE) return;
} 
```