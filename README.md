hypeStripePayments
==================

A wrapper for Stripe's PHP SDK

## Webhooks

Configure your Stripe application to send webhooks to ```https://<your-elgg-site>/payments/stripe/webhooks```

To digest a webhook, register a plugin hook handler:

```php
elgg_register_plugin_hook_handler('customer.subscription.deleted', 'stripe', HandleExpiredSubscription::class);

class HandleExpiredSubscription {
	public function __invoke(\Elgg\Hook $hook) {
		$stripe_event = $hook->getParam('event');
		/* @var $stripe_event \Stripe\Event */
		
		$subscription = $stripe_event->data->object;
		
		// ... do stuff
		
		return $result; // Result will be reported back to stripe
	}
}

```

## Card Input

To display a card input:

```php
// Card number, expiry and CVC
echo elgg_view_field([
	'#type' => 'stripe/card',
	'#label' => 'Credit or Debit Card',
	'required' => true,
]);

// Cardholder name
echo elgg_view_field([
	'#type' => 'stripe/cardholder',
	'#label' => 'Cardholder',
	'required' => true,
]);

// Billing address
// Requires hypeCountries plugin
echo elgg_view_field([
	'#type' => 'stripe/address',
	'#label' => 'Billing address',
	'required' => true,
]);
```

You can then retrieve the value of the Stripe token in your action:

```php
$payment_method = get_input('payment_method');
list($gateway, $source_id) = explode('::', $payment_method);

if ($gateway == 'stripe' && $source_id) {
    // Use $source_id to create a charge
    
} else {
   $token = get_input('stripe_token');
   $address = get_input('address');
   $name = get_input('cardholder');
   
   // Use stripe API to create a new card object
   // or use the token as the source of the payment
}
```