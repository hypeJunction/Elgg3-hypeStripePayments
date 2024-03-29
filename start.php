<?php

require_once __DIR__ . '/autoloader.php';

return function () {

	elgg_register_event_handler('init', 'system', function () {

		elgg()->payments->registerGateway(\hypeJunction\Stripe\StripeGateway::instance());

		elgg_register_plugin_hook_handler('elgg.data', 'page', \hypeJunction\Stripe\SetJsData::class);

		elgg_define_js('stripe', [
			'src' => 'https://js.stripe.com/v3/stripe.js',
			'exports' => 'window.Stripe',
		]);

		elgg_extend_view('elements/forms.css', 'input/stripe/card.css');

		elgg_register_ajax_view('payments/method/stripe/form');

		elgg_register_plugin_hook_handler('refund', 'payments', \hypeJunction\Stripe\RefundTransaction::class);

		elgg_register_plugin_hook_handler('charge.failed', 'stripe', \hypeJunction\Stripe\DigestChargeWebhook::class);
		elgg_register_plugin_hook_handler('charge.pending', 'stripe', \hypeJunction\Stripe\DigestChargeWebhook::class);
		elgg_register_plugin_hook_handler('charge.refunded', 'stripe', \hypeJunction\Stripe\DigestChargeWebhook::class);
		elgg_register_plugin_hook_handler('charge.succeeded', 'stripe', \hypeJunction\Stripe\DigestChargeWebhook::class);
		elgg_register_plugin_hook_handler('charge.updated', 'stripe', \hypeJunction\Stripe\DigestChargeWebhook::class);

		elgg_register_plugin_hook_handler('register', 'menu:page', \hypeJunction\Stripe\PageMenu::class);

		elgg_register_plugin_hook_handler('payments:sources', 'user', \hypeJunction\Stripe\SetUserPaymentSources::class);

	});

};
