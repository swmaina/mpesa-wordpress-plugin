<?php

namespace Woompesa\Checkout;

defined( 'ABSPATH' ) || exit;

class Availability {
	public static function currency_is_supported() {
		return function_exists( 'get_woocommerce_currency' ) && 'TZS' === get_woocommerce_currency();
	}
}
