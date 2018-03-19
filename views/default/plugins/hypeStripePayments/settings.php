<?php

$entity = elgg_extract('entity', $vars);

$link = elgg_view('output/url', [
	'href' => elgg_generate_url('payments:stripe:webhooks'),
]);

$message = elgg_echo('payments:stripe:settings:webhooks', [$link]);
echo elgg_view_message('notice', $message, [
	'title' => false,
]);

$fields = [
	'pk_test' => 'text',
	'sk_test' => 'text',
	'pk_live' => 'text',
	'sk_live' => 'text',
	'webhook_secret' => 'text',
];

foreach ($fields as $name => $options) {
	if (is_string($options)) {
		$options = [
			'#type' => $options,
		];
	}

	$options['name'] = "params[$name]";
	$options['value'] = $entity->$name;
	$options['#label'] = elgg_echo("payments:stripe:setting:$name");

	echo elgg_view_field($options);
}