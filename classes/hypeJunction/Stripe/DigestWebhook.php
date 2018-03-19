<?php

namespace hypeJunction\Stripe;

use Elgg\BadRequestException;
use Elgg\Http\ResponseBuilder;
use Elgg\HttpException;
use Elgg\Request;
use Stripe\Error\SignatureVerification;
use UnexpectedValueException;

class DigestWebhook {

	/**
	 * Digest stripe webhook
	 *
	 * @param Request $request Request
	 *
	 * @return ResponseBuilder
	 * @throws BadRequestException
	 * @throws HttpException
	 */
	public function __invoke(Request $request) {

		elgg_set_viewtype('json');

		elgg_set_http_header('Content-Type: application/json');

		$payload = _elgg_services()->request->getContent();

		try {
			if (empty($payload)) {
				throw new BadRequestException('Payload is empty');
			}

			$sig_header = _elgg_services()->request->server->get("HTTP_STRIPE_SIGNATURE");
			$event = null;

			$svc = elgg()->stripe;
			/* @var $svc \hypeJunction\Stripe\StripeClient */

			try {
				$event = \Stripe\Webhook::constructEvent(
					$payload, $sig_header, $svc->webhook_secret
				);
			} catch (UnexpectedValueException $e) {
				throw new BadRequestException('Payload data is corrupted');
			} catch (SignatureVerification $e) {
				throw new BadRequestException('Invalid signature');
			}

			$result = elgg_trigger_plugin_hook($event->type, 'stripe', ['event' => $event]);

			if ($result === false) {
				throw new HttpException('Event was not digested because one of the handlers refused to process data', ELGG_HTTP_INTERNAL_SERVER_ERROR);
			}
		} catch (\Elgg\HttpException $exception) {
			return elgg_ok_response(['error' => $exception->getMessage()], '', null, $exception->getCode());
		}

		return elgg_ok_response(['result' => $result]);
	}
}