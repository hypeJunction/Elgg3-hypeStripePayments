<?php

return [
	'actions' => [
		'payments/checkout/stripe' => [
			'controller' => \hypeJunction\Stripe\CheckoutAction::class,
			'access' => 'public',
		],
	],
	'routes' => [
		'payments:stripe:webhooks' => [
			'path' => '/payments/stripe/webhooks',
			'controller' => \hypeJunction\Stripe\DigestWebhook::class,
			'walled' => false,
		],
	],
];
