define(function (require) {

	var elgg = require('elgg');
	var Stripe = require('stripe');
	var Ajax = require('elgg/Ajax');
	var spinner = require('elgg/spinner');

	var api = {
		init: function (id) {
			var $elem = $(id);

			var stripe = Stripe(elgg.data.stripe_pk);
			var elements = stripe.elements();

			var card = elements.create('card', $elem.data('card'));

			card.mount($elem.find('.card-element')[0]);

			card.addEventListener('change', function (event) {
				if (event.error) {
					$elem.find('.card-errors').removeClass('hidden').text(event.error.message);
				} else {
					$elem.find('.card-errors').addClass('hidden').text('');
				}
			});

			var $form = $elem.closest('form');

			$form.uniqueId();

			$(document).on('submit', '#' + $form.attr('id'), function(e) {

				if (!$(this).has('[data-stripe]')) {
					return;
				}

				e.preventDefault();

				var cardData = api.getCardData($form);
				
				$('[type="submit"]', $form).prop('disabled', true);

				spinner.start(elgg.echo('payments:stripe:card:processing'));

				stripe.createToken(card, cardData).then(function (result) {
					var $token = $form.find('[name="stripe_token"]');

					if (result.token || !$token.data('required')) {
						$token.val(result.token.id);
						$form.get(0).submit();
					} else {
						$('[type="submit"]', $form).prop('disabled', true);
						$elem.find('.card-errors').removeClass('hidden').text(result.error.message);
					}

					spinner.stop();
				});

				return false;
			});
		},
		getCardData: function($form) {
			var ajax = new Ajax(false);
			var formData = ajax.objectify($form);

			var cardData = {};
			var props = {
				'name': 'cardholder',
				'address_line1': 'address[street_address]',
				'address_line2': 'address[extended_address]',
				'address_city': 'address[locality]',
				'address_state': 'address[region]',
				'address_zip': 'address[postal_code]',
				'address_country': 'address[country_code]'
			};

			$.each(props, function(index, value) {
				if (formData.has(value)) {
					cardData[index] = formData.get(value);
				}
			});

			return cardData;
		}
	};

	return api;
});