<?php

namespace hypeJunction\Stripe;

use Elgg\Hook;
use hypeJunction\Payments\Transaction;

class RefundTransaction {

	/**
	 * Initiate a refund
	 *
	 * @param Hook $hook Hook
	 * @return bool|null
	 */
	public function __invoke(Hook $hook) {
		if ($hook->getValue()) {
			// Transaction already refunded
			return null;
		}

		$transaction = $hook->getEntityParam();
		if (!$transaction instanceof Transaction) {
			return null;
		}

		if ($transaction->payment_method == 'stripe') {
			$adapter = StripeGateway::instance();
			return $adapter->refund($transaction);
		}
	}
}