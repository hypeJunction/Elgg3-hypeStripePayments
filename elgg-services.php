<?php

return [
	'stripe' => \DI\object(\hypeJunction\Stripe\StripeClient::class)
		->method('setup'),

	'payments.gateways.stripe' => \DI\object(\hypeJunction\Stripe\StripeGateway::class)
		->constructor(\DI\get('stripe')),

];
