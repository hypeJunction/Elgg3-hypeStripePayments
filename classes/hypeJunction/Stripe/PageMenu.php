<?php

namespace hypeJunction\Stripe;

use Elgg\Hook;

class PageMenu {

	public function __invoke(Hook $hook) {

		$menu = $hook->getValue();

		$menu[] = \ElggMenuItem::factory([
			'name' => 'payments:stripe:settings',
			'parent_name' => 'payments',
			'href' => 'admin/plugin_settings/hypeStripePayments',
			'text' => elgg_echo('payments:stripe:settings'),
			'icon' => 'cog',
			'context' => ['admin'],
			'section' => 'configure',
		]);

		$menu[] = \ElggMenuItem::factory([
			'name' => 'payments:stripe:transactions',
			'parent_name' => 'payments',
			'href' => 'admin/payments/stripe',
			'text' => elgg_echo('payments:stripe:transactions'),
			'icon' => 'exchange',
			'context' => ['admin'],
			'section' => 'configure',
		]);

		return $menu;
	}
}