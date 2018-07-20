<?php

namespace hypeJunction\Stripe;

use Elgg\Hook;

/**
 * Javascript config handler
 */
class SetJsData {

	/**
	 * Define stripe publishable key
	 *
	 * @param \Elgg\Hook $hook Hook info
	 *
	 * @return array
	 */
	public function __invoke(Hook $hook) {
		$value = $hook->getValue();

		$svc = StripeClient::instance();
		/* @var $svc \hypeJunction\Stripe\StripeClient */

		$value['stripe_pk'] = $svc->public_key;

		return $value;
	}
}
