<?php

$id = "card-input-" . base_convert(mt_rand(), 10, 36);

$card = elgg_format_element('div', [
	'class' => 'card-element',
]);

$errors = elgg_format_element('div', [
	'class' => 'card-errors hidden',
]);

$hidden = elgg_view_field([
	'#type' => 'hidden',
	'name' => 'stripe_token',
	'data-required' => elgg_extract('required', $vars, false),
]);

$attrs = [
	'id' => $id,
	'data-stripe' => '',
];

$config = elgg_extract('config', $vars, []);
$config['hidePostalCode'] = elgg_extract('hide_postal_code', $vars, true);

$attrs['data-card'] = json_encode($config);

echo elgg_format_element('div', $attrs, $card . $errors . $hidden);
?>

<script>
	require(['input/stripe/card'], function (card) {
		card.init('#<?= $id ?>');
	});
</script>