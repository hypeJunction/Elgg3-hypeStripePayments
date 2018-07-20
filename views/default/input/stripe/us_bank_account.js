define(function (require) {

	var elgg = require('elgg');
	var $ = require('jquery');
	var Stripe = require('stripe');
	var Ajax = require('elgg/Ajax');
	var Form = require('ajax/Form');

	var api = {
		init: function (id) {
			var $elem = $(id);

			var stripe = Stripe(elgg.data.stripe_pk);

			var $form = $elem.closest('form');
			var form = new Form($form);

			form.onSubmit(function (resolve, reject) {
				if (!$form.has('[data-stripe]')) {
					return resolve();
				}

				var $token = $form.find('[name="stripe_token"]');
				if ($token.val()) {
					return resolve();
				}

				var bankAccountData = api.getBankAccountData($form, $elem);

				stripe.createToken('bank_account', bankAccountData).then(function (result) {
					if (result.token || !$token.data('required')) {
						$token.val(result.token.id);
						return resolve();
					} else {
						$elem.find('.card-errors').removeClass('hidden').text(result.error.message);
						return reject(new Error(result.error.message));
					}
				});
			});
		},
		getBankAccountData: function ($form, $elem) {
			return {
				country: 'US',
				currency: 'usd',
				account_holder_type: 'individual',
				routing_number: $elem.find('[data-routing-number]').val(),
				account_number: $elem.find('[data-account-number]').val(),
				account_holder_name: $elem.find('[data-account-holder-name]').val(),
			};
		}
	};

	return api;
});