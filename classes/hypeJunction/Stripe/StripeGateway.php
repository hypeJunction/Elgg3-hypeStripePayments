<?php

namespace hypeJunction\Stripe;

use hypeJunction\Payments\Amount;
use hypeJunction\Payments\CreditCard;
use hypeJunction\Payments\GatewayInterface;
use hypeJunction\Payments\Payment;
use hypeJunction\Payments\Refund;
use hypeJunction\Payments\TransactionInterface;
use Stripe\Error\Base;
use Stripe\Error\Card;

class StripeGateway implements GatewayInterface {

	/**
	 * @var StripeClient
	 */
	protected $client;

	/**
	 * Constructor
	 *
	 * @param StripeClient $client Client
	 */
	public function __construct(StripeClient $client) {
		$this->client = $client;
	}

	/**
	 * {@inheritdoc}
	 */
	public function id() {
		return 'stripe';
	}

	/**
	 * {@inheritdoc}
	 */
	public function pay(TransactionInterface $transaction, array $params = []) {

		$transaction->setPaymentMethod('stripe');

		$source = elgg_extract('stripe_token', $params);

		if (!$source) {
			$transaction->setStatus(TransactionInterface::STATUS_FAILED);
			$error = elgg_echo('payments:stripe:no_source');

			return elgg_error_response($error);
		}

		$merchant = $transaction->getMerchant();
		$customer = $transaction->getCustomer();

		$description = $transaction->getDisplayName();
		if (!$description) {
			$description = "Payment to {$merchant->getDisplayName()}";
		}

		$order = $transaction->getOrder();
		$address = null;
		if ($order) {
			$shipping = $order->getShippingAddress();
			if ($shipping) {
				$address = [
					'city' => $shipping->locality,
					'country' => $shipping->country_code,
					'line1' => $shipping->street_address,
					'line2' => $shipping->extended_address,
					'postal_code' => $shipping->postal_code,
					'state' => $shipping->region,
				];
			}
		}

		$amount = $transaction->getAmount();

		try {
			$charge_params = [
				'amount' => $amount->getAmount(),
				'currency' => $amount->getCurrency(),
				'source' => $source,
				'description' => $description,
				'metadata' => [
					'invoice_id' => $transaction->guid,
					'transaction_id' => $transaction->getId(),
				],
				'receipt_email' => $customer->email,
				'statement_descriptor' => substr($description, 0, 22),
			];

			if ($address) {
				$charge_params['shipping'] = [
					'name' => $customer->getDisplayName(),
					'phone' => $customer->phone,
					'address' => $address,
				];
			}

			$charge = $this->client->createCharge($charge_params);

			$transaction->stripe_charge_id = $charge->id;

			$source = $charge->source;

			$brands = [
				'Visa' => 'visa',
				'MasterCard' => 'mastercard',
				'American Express' => 'amex',
				'JCB' => 'jcb',
				'Diners Club' => 'diners',
				'Discover' => 'discover',
			];

			$cc = new CreditCard();
			$cc->last4 = $source->last4;
			$cc->brand = elgg_extract($source->brand, $brands, $source->brand);
			$cc->id = $source->id;
			$cc->exp_month = $source->exp_month;
			$cc->exp_year = $source->exp_year;

			$transaction->setFundingSource($cc);

			$this->updateTransactionStatus($transaction);

			$data = [
				'entity' => $transaction,
				'action' => 'pay',
			];

			$message = elgg_echo("payments:stripe:pay:{$transaction->getStatus()}");

			return elgg_ok_response($data, $message);
		} catch (Card $ex) {
			elgg_log($ex->getMessage() . ': ' . print_r($ex->getJsonBody()), 'ERROR');

			$transaction->setStatus(TransactionInterface::STATUS_FAILED);

			$error = elgg_echo("payments:stripe:card_error:{$ex->getStripeCode()}");

			return elgg_error_response($error);
		} catch (Base $ex) {

			elgg_log($ex->getMessage() . ': ' . print_r($ex->getJsonBody()), 'ERROR');

			$transaction->setStatus(TransactionInterface::STATUS_FAILED);

			$error = elgg_echo("payments:stripe:api_error");

			return elgg_error_response($error);
		}
	}

	/**
	 * Update transaction status
	 *
	 * @param TransactionInterface $transaction Transaction
	 *
	 * @return TransactionInterface
	 */
	public function updateTransactionStatus(TransactionInterface $transaction) {

		if (!$transaction->stripe_charge_id) {
			return $transaction;
		}

		try {
			$charge = $this->client->getCharge($transaction->stripe_charge_id);
		} catch (Base $ex) {
			elgg_log($ex->getMessage() . ': ' . print_r($ex->getJsonBody()), 'ERROR');

			return $transaction;
		}

		if ($charge->status == 'pending') {
			if ($transaction->status != TransactionInterface::STATUS_PAYMENT_PENDING) {
				$transaction->setStatus(TransactionInterface::STATUS_PAYMENT_PENDING);
			}
		} else if ($charge->status == 'failed') {
			if ($transaction->status != TransactionInterface::STATUS_FAILED) {
				$transaction->setStatus(TransactionInterface::STATUS_FAILED);
			}
		} else if ($charge->amount_refunded > 0) {
			$payments = $transaction->getPayments();
			$payment_ids = array_map(function ($payment) {
				return $payment->stripe_refund_id;
			}, $payments);

			$processor_fee = $transaction->getProcessorFee()->getAmount();

			foreach ($charge->refunds->AutoPagingIterator() as $stripe_refund) {

				if (in_array($stripe_refund->id, $payment_ids)) {
					continue;
				}

				$stripe_balance_transaction = \Stripe\BalanceTransaction::retrieve($stripe_refund->balance_transaction);
				$processor_fee += $stripe_balance_transaction->fee;

				$refund = new Refund();
				$refund->setTimeCreated((int) $stripe_refund->created)
					->setAmount(new Amount(-$stripe_refund->amount, strtoupper($stripe_refund->currency)))
					->setPaymentMethod('stripe')
					->setDescription(elgg_echo('payments:refund'));
				$refund->stripe_refund_id = $stripe_refund->id;
				$transaction->addPayment($refund);
			}

			if ($charge->refunded) {
				if ($transaction->status != TransactionInterface::STATUS_REFUNDED) {
					$transaction->setStatus(TransactionInterface::STATUS_REFUNDED);
				}
			} else {
				if ($transaction->status != TransactionInterface::STATUS_PARTIALLY_REFUNDED) {
					$transaction->setStatus(TransactionInterface::STATUS_PARTIALLY_REFUNDED);
				}
			}

			$transaction->setProcessorFee(new Amount($processor_fee, $charge->currency));
		} else {
			if ($transaction->status != TransactionInterface::STATUS_PAID) {
				$payment = new Payment();
				$payment->setTimeCreated((int) $charge->created)
					->setAmount(new Amount((int) $charge->amount, strtoupper($charge->currency)))
					->setPaymentMethod('stripe')
					->setDescription(elgg_echo('payments:payment'));
				$payment->stripe_payment_id = $charge->id;
				$transaction->addPayment($payment);
				$transaction->setStatus(TransactionInterface::STATUS_PAID);

				$stripe_balance_transaction = \Stripe\BalanceTransaction::retrieve($charge->balance_transaction);

				$transaction->setProcessorFee(new Amount($stripe_balance_transaction->fee, $stripe_balance_transaction->currency));
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function refund(TransactionInterface $transaction) {
		if (!$transaction->stripe_charge_id) {
			return false;
		}

		try {
			$this->client->createRefund([
				'charge' => $transaction->stripe_charge_id,
				'metadata' => [
					'invoice_id' => $transaction->guid,
					'transaction_id' => $transaction->getId(),
				],
			]);

			$this->updateTransactionStatus($transaction);

			return true;
		} catch (Base $ex) {
			elgg_log($ex->getMessage() . ': ' . print_r($ex->getJsonBody()), 'ERROR');

			return false;
		}
	}

}
