<?php
/**
 * Plugin Name: M-PESA Tanzania for Woocommerce
 * Description: Vodacom M-PESA Payment Gateway for WooCommerce - Tanzania (TZS). Modernized foundation for M-Pesa OpenAPI C2B payments.
 * Version: 2.0.1
 * Author: Sam Maina
 * Author URI: https://mindsafe.co.ke
 * Licence: MIT
 * WC requires at least: 7.9
 * WC tested up to: 9.8
 */

defined( 'ABSPATH' ) || exit;

define( 'WOOMPESA_FILE', __FILE__ );
define( 'WOOMPESA_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOMPESA_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOMPESA_VERSION', '2.0.1' );

require_once WOOMPESA_PATH . 'includes/Autoloader.php';

\Woompesa\Autoloader::register();

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		\Woompesa\Plugin::instance()->boot();
	}
);
