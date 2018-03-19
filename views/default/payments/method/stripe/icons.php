<?php

$stripe = elgg()->stripe;
/* @var $stripe \hypeJunction\Stripe\StripeClient */

$account = $stripe->getAccount();
if (!$account instanceof \Stripe\Account) {
	return;
}

$brands = array('visa', 'mastercard', 'amex');
if ($account->country == 'US') {
	array_push($brands, 'jcb', 'diners', 'discover');
}

array_walk($brands, function(&$elem) {
	$elem = elgg_view('output/img', [
		'src' => elgg_get_simplecache_url("payments/icons/$elem.png"),
	]);
});

echo implode('&nbsp;', $brands);


