<?php

namespace Woompesa;

use Woompesa\Admin\Settings;
use Woompesa\Admin\DiagnosticsPage;
use Woompesa\Gateway\Gateway;
use Woompesa\Gateway\BlocksSupport;
use Woompesa\Orders\ReconciliationService;
use Woompesa\Webhook\CallbackController;

defined( 'ABSPATH' ) || exit;

class Plugin {
	/**
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * @var bool
	 */
	private $booted = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		Settings::register_hooks();
		DiagnosticsPage::register_hooks();
		CallbackController::register_hooks();
		ReconciliationService::register_hooks();

		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_blocks_support' ) );
	}

	public function register_gateway( $methods ) {
		$methods[] = Gateway::class;

		return $methods;
	}

	public function register_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_register_style(
			'woompesa-checkout',
			WOOMPESA_URL . 'assets/css/checkout.css',
			array(),
			WOOMPESA_VERSION
		);

		wp_register_script(
			'woompesa-checkout-classic',
			WOOMPESA_URL . 'assets/js/checkout-classic.js',
			array( 'jquery' ),
			WOOMPESA_VERSION,
			true
		);
	}

	public function register_blocks_support() {
		if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			return;
		}

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function ( $payment_method_registry ) {
				$payment_method_registry->register( new BlocksSupport() );
			}
		);
	}
}
