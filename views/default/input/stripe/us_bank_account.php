<?php

$id = "bank-account-input-" . base_convert(mt_rand(), 10, 36);

$account = elgg_view_field([
    '#type' => 'fieldset',
    'align' => 'horizontal',
    'fields' => [
		[
			'#type' => 'text',
			'#label' => elgg_echo('payments:stripe:account_holder_name'),
			'data-account-holder-name' => '',
		],
        [
            '#type' => 'text',
            '#label' => elgg_echo('payments:stripe:routing_number'),
            'data-routing-number' => '',
        ],
		[
			'#type' => 'text',
			'#label' => elgg_echo('payments:stripe:account_number'),
			'data-account-number' => '',
		],
    ]
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

echo elgg_format_element('div', $attrs, $account . $errors . $hidden);
?>

<script>
	require(['input/stripe/us_bank_account'], function (card) {
		card.init('#<?= $id ?>');
	});
</script>