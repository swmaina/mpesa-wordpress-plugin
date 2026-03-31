<?php

namespace Woompesa\Gateway;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

class BlocksSupport extends AbstractPaymentMethodType {
	protected $name = 'woompesa_tz';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_woompesa_tz_settings', array() );
	}

	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'woompesa-checkout-blocks',
			WOOMPESA_URL . 'assets/js/checkout-blocks.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			WOOMPESA_VERSION,
			true
		);

		return array( 'woompesa-checkout-blocks' );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => $this->settings['title'] ?? __( 'M-Pesa Tanzania', 'woompesa' ),
			'description' => $this->settings['description'] ?? __( 'Authorize the payment on your phone after placing the order.', 'woompesa' ),
			'supports'    => array(
				'features' => array( 'products' ),
			),
			'icon_url'    => WOOMPESA_URL . 'img/logo.jpg',
		);
	}
}
