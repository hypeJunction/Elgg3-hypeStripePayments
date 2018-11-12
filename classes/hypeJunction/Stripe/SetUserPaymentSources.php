<?php

namespace hypeJunction\Stripe;

use Elgg\Hook;
use hypeJunction\Payments\PaymentSource;

class SetUserPaymentSources {

	/**
	 * Add user cards to payment sources
	 *
	 * @param Hook $hook Hook
	 *
	 * @return array|mixed|null
	 */
	public function __invoke(Hook $hook) {

		$user = $hook->getEntityParam();
		if (!$user instanceof \ElggUser) {
			return null;
		}

		$sources = $hook->getValue();

		$customer = StripeClient::instance()->createCustomer($user);

		foreach ($customer->sources->all()->data as $source) {
			$brand = strtolower($source->brand);
			$sources[] = new PaymentSource(
				StripeGateway::instance(),
				$source->id,
				[
					'icon' => elgg_get_simplecache_url("payments/icons/$brand.png"),
					'label' => "xxxx-{$source->last4} <small>[{$source->exp_month}/{$source->exp_year}]</small>",
				]
			);
		}

		return $sources;
	}
}