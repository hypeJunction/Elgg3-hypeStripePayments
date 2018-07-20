<?php

namespace hypeJunction\Stripe;

use Elgg\Di\ServiceFacade;
use ElggUser;
use Stripe\Account;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\CountrySpec;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\Plan;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Subscription;

/**
 * @property string $secret_key
 * @property string $public_key
 * @property string $webhook_secret
 */
class StripeClient {

	use ServiceFacade;

	const API_VERSION = '2018-02-28';

	/**
	 * {@inheritdoc}
	 */
	public static function name() {
		return 'stripe';
	}

	/**
	 * Configure the client
	 * @return void
	 */
	public function setup() {

		$this->environment = elgg_get_plugin_setting('environment', 'hypePayments');
		$this->webhook_secret = elgg_get_plugin_setting('webhook_secret', 'hypeStripePayments');

		switch ($this->environment) {
			default :
				$this->secret_key = elgg_get_plugin_setting('sk_test', 'hypeStripePayments');
				$this->public_key = elgg_get_plugin_setting('pk_test', 'hypeStripePayments');
				break;

			case 'production' :
				$this->secret_key = elgg_get_plugin_setting('sk_live', 'hypeStripePayments');
				$this->public_key = elgg_get_plugin_setting('pk_live', 'hypeStripePayments');
				break;
		}

		Stripe::setApiKey($this->secret_key);

		Stripe::setApiVersion(self::API_VERSION);

		$site = elgg_get_site_entity();
		Stripe::setAppInfo($site->name, '1.0', $site->url);
	}

	/**
	 * {@inheritdoc}
	 */
	public function __get($name) {
		return $this->$name;
	}

	/**
	 * Create a new customer
	 *
	 * @param ElggUser $user   User
	 * @param array    $params Params
	 *
	 * @return Customer
	 */
	public function createCustomer(ElggUser $user = null, array $params = []) {

		if ($user && $user->stripe_id) {
			try {
				return $this->getCustomer($user->stripe_id);
			} catch (\Exception $ex) {

			}
		}

		if ($user) {
			$params['email'] = $user->email;
			$params['description'] = $user->getDisplayName();
			$params['metadata']['guid'] = $user->guid;
			$params['metadata']['username'] = $user->username;
		}

		$customer = Customer::create($params);

		$user->stripe_id = $customer->id;

		return $customer;
	}

	/**
	 * Get a customer from id
	 *
	 * @param string $customer_id Customer ID
	 *
	 * @return Customer
	 */
	public function getCustomer($customer_id) {
		return Customer::retrieve($customer_id);
	}

	/**
	 * Get all customers
	 *
	 * @param array $params Params
	 *
	 * @return \Stripe\Collection
	 */
	public function getCustomers(array $params = []) {
		return Customer::all($params);
	}

	/**
	 * Retrieve account
	 *
	 * @param string $id Account ID
	 *
	 * @return Account
	 */
	public function getAccount($id = null) {
		return Account::retrieve($id);
	}

	/**
	 * Retrieve country spec
	 *
	 * @param string $country Country ID
	 *
	 * @return CountrySpec
	 */
	public function getCountrySpec($country) {
		return CountrySpec::retrieve($country);
	}

	/**
	 * Create a refund
	 *
	 * @param array $params Params
	 *
	 * @return Refund
	 */
	public function createRefund(array $params = []) {
		return Refund::create($params);
	}

	/**
	 * Retrieve a refund
	 *
	 * @param string $refund_id Refund ID
	 *
	 * @return Refund
	 */
	public function getRefund($refund_id) {
		return Refund::retrieve($refund_id);
	}

	/**
	 * Get all refunds
	 *
	 * @param array $params Params
	 *
	 * @return \Stripe\Collection
	 */
	public function getRefunds(array $params = []) {
		return Refund::all($params);
	}

	/**
	 * Create a charge
	 *
	 * @param array $params Params
	 *
	 * @return Charge
	 */
	public function createCharge(array $params = []) {
		return Charge::create($params);
	}

	/**
	 * Retrieve a charge
	 *
	 * @param string $charge_id Charge ID
	 *
	 * @return Charge
	 */
	public function getCharge($charge_id) {
		return Charge::retrieve($charge_id);
	}

	/**
	 * Get all charges
	 *
	 * @param array $params Params
	 *
	 * @return \Stripe\Collection
	 */
	public function getCharges(array $params = []) {
		return Charge::all($params);
	}

	/**
	 * Create a invoice
	 *
	 * @param array $params Params
	 *
	 * @return Invoice
	 */
	public function createInvoice(array $params = []) {
		return Invoice::create($params);
	}

	/**
	 * Retrieve a invoice
	 *
	 * @param string $invoice_id Invoice ID
	 *
	 * @return Invoice
	 */
	public function getInvoice($invoice_id) {
		return Invoice::retrieve($invoice_id);
	}

	/**
	 * Get all invoices
	 *
	 * @param array $params Params
	 *
	 * @return \Stripe\Collection
	 */
	public function getInvoices(array $params = []) {
		return Invoice::all($params);
	}

	/**
	 * Retrieve a balance_transaction
	 *
	 * @param string $balance_transaction_id BalanceTransaction ID
	 *
	 * @return BalanceTransaction
	 */
	public function getBalanceTransaction($balance_transaction_id) {
		return BalanceTransaction::retrieve($balance_transaction_id);
	}

	/**
	 * Get all balance_transactions
	 *
	 * @param array $params Params
	 *
	 * @return \Stripe\Collection
	 */
	public function getBalanceTransactions(array $params = []) {
		return BalanceTransaction::all($params);
	}

	/**
	 * Create a new subscription plan
	 *
	 * @param array $params Params
	 *
	 * @return Plan
	 */
	public function createPlan(array $params = []) {
		return Plan::create($params);
	}

	/**
	 * Get a plan by its id
	 *
	 * @param string $plan_id Plan ID
	 *
	 * @return Plan
	 */
	public function getPlan($plan_id) {
		return Plan::retrieve($plan_id);
	}

	/**
	 * Get all plans
	 *
	 * @param array $params Plans
	 *
	 * @return \Stripe\Collection
	 */
	public function getPlans(array $params = []) {
		return Plan::all($params);
	}

	/**
	 * Create a new subscription
	 *
	 * @param array $params Params
	 *
	 * @return Subscription
	 */
	public function createSubscription(array $params = []) {
		return Subscription::create($params);
	}

	/**
	 * Retrieve a subscription from ID
	 *
	 * @param string $id ID
	 *
	 * @return Subscription
	 */
	public function getSubscription($id) {
		return Subscription::retrieve($id);
	}
}